<?php

// app/Jobs/RunMolecularDynamicsJob.php

namespace App\Jobs;

use App\Models\Simulation;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RunMolecularDynamicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    protected $simulation;
    public $timeout = 43200; // 12 hours
    public $tries = 1;

    public function __construct(Simulation $simulation)
    {
        $this->simulation = $simulation;
    }

    public function handle()
    {
        $simulationId = $this->simulation->id;
        $simulationUid = 'sim_' . $simulationId;

        $workDir = storage_path("app/simulations/{$simulationUid}");
        $scriptsDir = base_path("scripts");

        // Create working directories
        if (!is_dir($workDir)) {
            mkdir($workDir, 0777, true);
        }

        $inputsDir = "$workDir/inputs";
        if (!is_dir($inputsDir)) {
            mkdir($inputsDir, 0777, true);
        }

        $outputsDir = "$workDir/outputs";
        if (!is_dir($outputsDir)) {
            mkdir($outputsDir, 0777, true);
        }

        $tempDir = "$workDir/temp";
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        try {
            $this->updateProgress(5, 'preparing', 'Preparing files...');

            // Copy input files
            $proteinPath = storage_path("app/" . $this->simulation->protein);
            $proteinDest = "$inputsDir/protein.pdb";

            if (!file_exists($proteinPath)) {
                throw new \Exception("Protein file not found: {$proteinPath}");
            }
            copy($proteinPath, $proteinDest);
            Log::info("Protein file copied to: {$proteinDest}");

            if ($this->simulation->ligand) {
                $ligandPath = storage_path("app/" . $this->simulation->ligand);
                $ligandDest = "$inputsDir/ligand.pdb";
                if (file_exists($ligandPath)) {
                    copy($ligandPath, $ligandDest);
                    Log::info("Ligand file copied to: {$ligandDest}");
                }
            }

            $this->updateProgress(10, 'preparing', 'Copying scripts...');

            // Copy modified Python scripts
            $pythonScript = "$scriptsDir/md_simulation.py";
            $precheckScript = "$scriptsDir/precheck.py";

            if (!file_exists($pythonScript)) {
                throw new \Exception("Python script not found: {$pythonScript}");
            }

            copy($pythonScript, "$workDir/md_simulation.py");
            copy($precheckScript, "$workDir/precheck.py");

            // Create config file for Python scripts
            $this->createPythonConfig($workDir);

            $this->updateProgress(20, 'running', 'Running MD simulation...');

            // Run precheck first
            $precheckResult = $this->runPrecheckInWSL($workDir);
            if (!$precheckResult['success']) {
                throw new \Exception("Precheck failed: " . $precheckResult['error']);
            }

            // Run MD simulation
            $result = $this->runMDSimulationInWSL($workDir);

            if (!$result['success']) {
                throw new \Exception("MD Simulation failed: " . $result['error']);
            }

            $this->updateProgress(90, 'collecting', 'Collecting results...');

            // Collect results
            $analysis = $this->collectResults($workDir);

            // Save output files to storage
            $this->saveOutputFiles($workDir, $simulationUid);

            $this->simulation->update([
                'status' => 'completed',
                'progress' => 100,
                'analysis' => $analysis,
                'completed_at' => now(),
            ]);

            Log::info("MD Simulation completed successfully for simulation {$simulationId}");
        } catch (\Exception $e) {
            Log::error("Job failed for simulation {$this->simulation->id}: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            $this->simulation->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function createPythonConfig($workDir)
    {
        $configContent = <<<'PHP'
<?php
// This file is used by Python scripts
// Configuration for MD simulation
$config = [
    'base_dir' => '{{WORK_DIR}}'
];
PHP;

        $configContent = str_replace('{{WORK_DIR}}', $workDir, $configContent);
        file_put_contents("$workDir/config.php", $configContent);
    }

    private function runPrecheckInWSL($workDir)
    {
        $wslWorkDir = $this->toWslPath($workDir);
        $condaInit = 'source /home/hhazm/miniconda3/etc/profile.d/conda.sh && ';

        // Pass work directory to Python script
        $command = "cd {$wslWorkDir} && {$condaInit} conda activate md_sim && python precheck.py {$wslWorkDir} 2>&1";

        Log::info("Precheck command: {$command}");

        $process = new Process(['wsl', '-d', 'Ubuntu', 'bash', '-c', $command]);
        $process->setTimeout(3600); // 1 hour timeout for precheck
        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        Log::info("Precheck output: " . substr($output, 0, 500));

        if (!$process->isSuccessful()) {
            return ['success' => false, 'error' => $errorOutput ?: $output];
        }

        return ['success' => true];
    }

    private function runMDSimulationInWSL($workDir)
    {
        $wslWorkDir = $this->toWslPath($workDir);
        $condaInit = 'source /home/hhazm/miniconda3/etc/profile.d/conda.sh && ';

        // Pass work directory to Python script
        $command = "cd {$wslWorkDir} && {$condaInit} conda activate md_sim && python md_simulation.py {$wslWorkDir} 2>&1";

        Log::info("MD command: {$command}");

        $process = new Process(['wsl', '-d', 'Ubuntu', 'bash', '-c', $command]);
        $process->setTimeout(43200); // 12 hours timeout
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                Log::error("MD Simulation Error: " . $buffer);
            } else {
                Log::info("MD Simulation Output: " . $buffer);
            }
        });

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();

        Log::info("MD output (first 1000 chars): " . substr($output, 0, 1000));

        if (!$process->isSuccessful()) {
            return ['success' => false, 'error' => $errorOutput ?: $output];
        }

        return ['success' => true];
    }

    private function toWslPath($windowsPath)
    {
        // Convert Windows path to WSL path
        // Example: C:\project\path -> /mnt/c/project/path
        $path = str_replace('\\', '/', $windowsPath);
        $path = preg_replace('/^([A-Za-z]):/', '/mnt/$1', $path);
        return strtolower($path);
    }

    private function updateProgress($progress, $status, $message = null)
    {
        $this->simulation->update([
            'progress' => $progress,
            'status' => $status,
            'error_message' => $message
        ]);
    }

    private function collectResults($workDir)
    {
        $analysis = [
            'summary' => [],
            'files' => []
        ];

        $analysisDir = "$workDir/outputs/analysis";

        // Collect RMSD data
        if (file_exists("$analysisDir/rmsd.csv")) {
            $analysis['summary']['rmsd_mean'] = $this->getMeanFromCSV("$analysisDir/rmsd.csv", 'RMSD_A');
            $analysis['summary']['rmsd_std'] = $this->getStdFromCSV("$analysisDir/rmsd.csv", 'RMSD_A');
            $analysis['files']['rmsd'] = "rmsd.csv";
        }

        // Collect Radius of Gyration data
        if (file_exists("$analysisDir/radius_of_gyration.csv")) {
            $analysis['summary']['rg_mean'] = $this->getMeanFromCSV("$analysisDir/radius_of_gyration.csv", 'Rg_A');
            $analysis['files']['radius_of_gyration'] = "radius_of_gyration.csv";
        }

        // Collect RMSF data if exists
        if (file_exists("$analysisDir/rmsf.csv")) {
            $analysis['summary']['rmsf_max'] = $this->getMaxFromCSV("$analysisDir/rmsf.csv", 'RMSF_A');
            $analysis['files']['rmsf'] = "rmsf.csv";
        }

        // Check for trajectory files
        $trajDir = "$workDir/outputs/trajectories";
        if (file_exists("$trajDir/trajectory.dcd")) {
            $analysis['files']['trajectory'] = "trajectory.dcd";
        }

        // Check for structure files
        $structDir = "$workDir/outputs/structures";
        if (file_exists("$structDir/final.pdb")) {
            $analysis['files']['final_structure'] = "final.pdb";
        }

        return $analysis;
    }

    private function saveOutputFiles($workDir, $simulationUid)
    {
        // Save analysis files to storage
        $analysisDir = "$workDir/outputs/analysis";
        if (is_dir($analysisDir)) {
            $files = glob("$analysisDir/*.{csv,txt,dat}", GLOB_BRACE);
            foreach ($files as $file) {
                $filename = basename($file);
                Storage::disk('local')->put(
                    "simulations/{$simulationUid}/analysis/{$filename}",
                    file_get_contents($file)
                );
            }
        }

        // Save trajectory if exists
        $trajFile = "$workDir/outputs/trajectories/trajectory.dcd";
        if (file_exists($trajFile)) {
            Storage::disk('local')->put(
                "simulations/{$simulationUid}/trajectory.dcd",
                file_get_contents($trajFile)
            );
        }

        // Save final structure
        $finalStruct = "$workDir/outputs/structures/final.pdb";
        if (file_exists($finalStruct)) {
            Storage::disk('local')->put(
                "simulations/{$simulationUid}/final.pdb",
                file_get_contents($finalStruct)
            );
        }
    }

    private function getMeanFromCSV($filePath, $column)
    {
        $values = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $colIndex = array_search($column, $headers);
            if ($colIndex !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (isset($row[$colIndex]) && is_numeric($row[$colIndex])) {
                        $values[] = floatval($row[$colIndex]);
                    }
                }
            }
            fclose($handle);
        }
        return empty($values) ? null : round(array_sum($values) / count($values), 3);
    }

    private function getStdFromCSV($filePath, $column)
    {
        $values = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $colIndex = array_search($column, $headers);
            if ($colIndex !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (isset($row[$colIndex]) && is_numeric($row[$colIndex])) {
                        $values[] = floatval($row[$colIndex]);
                    }
                }
            }
            fclose($handle);
        }

        if (empty($values)) return null;

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function ($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / count($values);

        return round(sqrt($variance), 3);
    }

    private function getMaxFromCSV($filePath, $column)
    {
        $values = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $colIndex = array_search($column, $headers);
            if ($colIndex !== false) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (isset($row[$colIndex]) && is_numeric($row[$colIndex])) {
                        $values[] = floatval($row[$colIndex]);
                    }
                }
            }
            fclose($handle);
        }
        return empty($values) ? null : round(max($values), 3);
    }
}
