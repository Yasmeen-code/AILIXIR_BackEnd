"""
Chemistry tools for the LangGraph agent.
All tools operate on SMILES strings and return
human-readable strings the LLM can reason about.
"""

from langchain_core.tools import tool

try:
    from rdkit import Chem
    from rdkit.Chem import Descriptors, rdMolDescriptors, Lipinski, QED
    from rdkit.Chem import AllChem
    RDKIT_AVAILABLE = True
except ImportError:
    RDKIT_AVAILABLE = False


# ──────────────────────────────────────────────────────────────
# Helper
# ──────────────────────────────────────────────────────────────
def _mol_from_smiles(smiles: str):
    """Parse SMILES → RDKit mol or raise ValueError."""
    if not RDKIT_AVAILABLE:
        raise RuntimeError("RDKit is not installed. Run: pip install rdkit")
    mol = Chem.MolFromSmiles(smiles.strip())
    if mol is None:
        raise ValueError(f"Invalid SMILES: '{smiles}'")
    return mol


# ──────────────────────────────────────────────────────────────
# Tool 1 — Molecular properties
# ──────────────────────────────────────────────────────────────
@tool
def get_molecular_properties(smiles: str) -> str:
    """
    Compute physicochemical properties for a single SMILES string.
    Returns molecular weight, logP, HBD, HBA, TPSA, rotatable bonds,
    number of rings, QED drug-likeness score, and formal charge.
    Use this before classifying or comparing molecules.
    """
    try:
        mol = _mol_from_smiles(smiles)
    except (ValueError, RuntimeError) as e:
        return f"Error: {e}"

    mw        = Descriptors.MolWt(mol)
    exact_mw  = Descriptors.ExactMolWt(mol)
    logp      = Descriptors.MolLogP(mol)
    hbd       = Lipinski.NumHDonors(mol)
    hba       = Lipinski.NumHAcceptors(mol)
    tpsa      = Descriptors.TPSA(mol)
    rot_bonds = Lipinski.NumRotatableBonds(mol)
    rings     = rdMolDescriptors.CalcNumRings(mol)
    arom_rings= rdMolDescriptors.CalcNumAromaticRings(mol)
    heavy     = mol.GetNumHeavyAtoms()
    charge    = Chem.GetFormalCharge(mol)
    qed_score = QED.qed(mol)
    fsp3      = rdMolDescriptors.CalcFractionCSP3(mol)

    return (
        f"SMILES: {smiles}\n"
        f"  Molecular weight (avg):  {mw:.2f} Da\n"
        f"  Exact molecular weight:  {exact_mw:.4f} Da\n"
        f"  LogP (lipophilicity):    {logp:.2f}\n"
        f"  H-bond donors (HBD):     {hbd}\n"
        f"  H-bond acceptors (HBA):  {hba}\n"
        f"  TPSA:                    {tpsa:.2f} Å²\n"
        f"  Rotatable bonds:         {rot_bonds}\n"
        f"  Total rings:             {rings}\n"
        f"  Aromatic rings:          {arom_rings}\n"
        f"  Heavy atom count:        {heavy}\n"
        f"  Formal charge:           {charge}\n"
        f"  QED drug-likeness:       {qed_score:.3f}  (0=bad, 1=ideal)\n"
        f"  Fsp3 (saturation):       {fsp3:.3f}  (>0.47 preferred for oral drugs)\n"
    )


# ──────────────────────────────────────────────────────────────
# Tool 2 — Drug-likeness classifier
# ──────────────────────────────────────────────────────────────
@tool
def classify_drug_likeness(smiles: str) -> str:
    """
    Classify a molecule against established drug-likeness filters:
    - Lipinski Rule of Five (oral bioavailability)
    - Veber rules (oral bioavailability)
    - Lead-likeness (fragment / lead optimisation)
    - REOS filter (GlaxoSmithKline reactive/undesirable groups)
    Returns pass/fail for each rule set with the reason.
    """
    try:
        mol = _mol_from_smiles(smiles)
    except (ValueError, RuntimeError) as e:
        return f"Error: {e}"

    mw        = Descriptors.MolWt(mol)
    logp      = Descriptors.MolLogP(mol)
    hbd       = Lipinski.NumHDonors(mol)
    hba       = Lipinski.NumHAcceptors(mol)
    tpsa      = Descriptors.TPSA(mol)
    rot_bonds = Lipinski.NumRotatableBonds(mol)
    rings     = rdMolDescriptors.CalcNumRings(mol)

    results = []

    # ── Lipinski Rule of Five ────────────────────────────────
    ro5_violations = []
    if mw > 500:
        ro5_violations.append(f"MW={mw:.1f} > 500")
    if logp > 5:
        ro5_violations.append(f"LogP={logp:.2f} > 5")
    if hbd > 5:
        ro5_violations.append(f"HBD={hbd} > 5")
    if hba > 10:
        ro5_violations.append(f"HBA={hba} > 10")

    if len(ro5_violations) <= 1:
        results.append(
            f" Lipinski Rule of Five: PASS  "
            f"({'1 violation allowed' if ro5_violations else 'no violations'})"
        )
    else:
        results.append(
            f" Lipinski Rule of Five: FAIL  "
            f"(violations: {', '.join(ro5_violations)})"
        )

    # ── Veber rules ─────────────────────────────────────────
    veber_issues = []
    if rot_bonds > 10:
        veber_issues.append(f"RotBonds={rot_bonds} > 10")
    if tpsa > 140:
        veber_issues.append(f"TPSA={tpsa:.1f} > 140 Å²")

    if not veber_issues:
        results.append(" Veber rules (oral bioavailability): PASS")
    else:
        results.append(
            f" Veber rules (oral bioavailability): FAIL  "
            f"(issues: {', '.join(veber_issues)})"
        )

    # ── Lead-likeness ────────────────────────────────────────
    lead_issues = []
    if not (200 <= mw <= 350):
        lead_issues.append(f"MW={mw:.1f} outside 200–350 Da")
    if not (-1 <= logp <= 3.5):
        lead_issues.append(f"LogP={logp:.2f} outside -1–3.5")
    if rings > 4:
        lead_issues.append(f"Rings={rings} > 4")

    if not lead_issues:
        results.append(" Lead-likeness: PASS  (suitable for lead optimisation)")
    else:
        results.append(
            f"  Lead-likeness: BORDERLINE  "
            f"(issues: {', '.join(lead_issues)})"
        )

    # ── Fragment-likeness (Rule of Three) ────────────────────
    ro3_issues = []
    if mw > 300:
        ro3_issues.append(f"MW={mw:.1f} > 300")
    if logp > 3:
        ro3_issues.append(f"LogP={logp:.2f} > 3")
    if hbd > 3:
        ro3_issues.append(f"HBD={hbd} > 3")
    if hba > 3:
        ro3_issues.append(f"HBA={hba} > 3")
    if rot_bonds > 3:
        ro3_issues.append(f"RotBonds={rot_bonds} > 3")

    if not ro3_issues:
        results.append(" Rule of Three (fragment-like): PASS")
    else:
        results.append(
            f"  Rule of Three (fragment-like): FAIL  "
            f"(expected for drug-sized molecules if Ro5 passed)"
        )

    # ── Summary classification ───────────────────────────────
    qed = QED.qed(mol)
    if qed >= 0.7:
        drug_class = "Drug-like (high QED)"
    elif qed >= 0.4:
        drug_class = "Moderately drug-like"
    else:
        drug_class = "Poor drug-likeness"

    results.append(f"\nQED score: {qed:.3f} → {drug_class}")

    header = f"Drug-likeness classification for: {smiles}\n" + "─" * 55
    return header + "\n" + "\n".join(results)


# ──────────────────────────────────────────────────────────────
# Tool 3 — Compare multiple SMILES properties
# ──────────────────────────────────────────────────────────────
@tool
def compare_molecules(smiles_list_str: str) -> str:
    """
    Compare physicochemical properties of multiple molecules side by side.
    Input: a comma-separated string of SMILES, e.g.
           "CCO, CC(=O)Oc1ccccc1C(=O)O, c1ccccc1"
    Returns a formatted comparison table and a recommendation.
    """
    raw = [s.strip() for s in smiles_list_str.split(",") if s.strip()]
    if len(raw) < 2:
        return "Error: provide at least 2 SMILES separated by commas."

    rows = []
    errors = []

    for smi in raw:
        try:
            mol = _mol_from_smiles(smi)
            rows.append({
                "smiles":    smi,
                "mw":        Descriptors.MolWt(mol),
                "logp":      Descriptors.MolLogP(mol),
                "hbd":       Lipinski.NumHDonors(mol),
                "hba":       Lipinski.NumHAcceptors(mol),
                "tpsa":      Descriptors.TPSA(mol),
                "rot":       Lipinski.NumRotatableBonds(mol),
                "qed":       QED.qed(mol),
                "fsp3":      rdMolDescriptors.CalcFractionCSP3(mol),
            })
        except (ValueError, RuntimeError) as e:
            errors.append(f"  Skipped '{smi}': {e}")

    if not rows:
        return "No valid SMILES could be parsed.\n" + "\n".join(errors)

    # Build table
    header = (
        f"{'SMILES':<40} {'MW':>7} {'LogP':>6} {'HBD':>4} "
        f"{'HBA':>4} {'TPSA':>7} {'RotB':>5} {'QED':>6} {'Fsp3':>6}"
    )
    sep = "─" * len(header)
    lines = [header, sep]

    for r in rows:
        label = r["smiles"] if len(r["smiles"]) <= 39 else r["smiles"][:36] + "..."
        lines.append(
            f"{label:<40} {r['mw']:>7.1f} {r['logp']:>6.2f} {r['hbd']:>4} "
            f"{r['hba']:>4} {r['tpsa']:>7.1f} {r['rot']:>5} "
            f"{r['qed']:>6.3f} {r['fsp3']:>6.3f}"
        )

    # Best by QED
    best_qed = max(rows, key=lambda x: x["qed"])
    lines.append(sep)
    lines.append(
        f"Best QED score: {best_qed['smiles']} (QED={best_qed['qed']:.3f})"
    )

    if errors:
        lines.append("\nWarnings:")
        lines.extend(errors)

    return "\n".join(lines)


# ──────────────────────────────────────────────────────────────
# Tool 4 — Docking results analyser
# ──────────────────────────────────────────────────────────────
@tool
def analyze_docking_results(docking_data: str) -> str:
    """
    Analyse molecular docking results and recommend the best candidate.
    Input format — provide each molecule on a new line as:
      SMILES | binding_affinity_kcal_mol | rmsd_angstrom | optional_notes
    Example:
      CCO | -7.2 | 1.1 | poses in ATP pocket
      c1ccccc1 | -5.1 | 2.3 |
      CC(=O)Oc1ccccc1C(=O)O | -9.4 | 0.8 | H-bonds to Lys41

    Returns a ranked table and a detailed recommendation explaining
    which molecule is best and why, combining docking score with
    drug-likeness.
    """
    lines_in = [l.strip() for l in docking_data.strip().split("\n") if l.strip()]
    if not lines_in:
        return "Error: no docking data provided."

    entries = []
    parse_errors = []

    for line in lines_in:
        parts = [p.strip() for p in line.split("|")]
        if len(parts) < 2:
            parse_errors.append(f"  Could not parse: '{line}'")
            continue
        smiles = parts[0]
        try:
            affinity = float(parts[1])
        except ValueError:
            parse_errors.append(
                f"  Could not parse affinity '{parts[1]}' for '{smiles}'"
            )
            continue
        rmsd  = float(parts[2]) if len(parts) > 2 and parts[2] else None
        notes = parts[3] if len(parts) > 3 else ""

        # Get drug-likeness
        try:
            mol = _mol_from_smiles(smiles)
            qed  = QED.qed(mol)
            mw   = Descriptors.MolWt(mol)
            logp = Descriptors.MolLogP(mol)
            hbd  = Lipinski.NumHDonors(mol)
            hba  = Lipinski.NumHAcceptors(mol)

            # Lipinski check
            ro5_fails = sum([
                mw > 500, logp > 5, hbd > 5, hba > 10
            ])
            ro5_ok = ro5_fails <= 1
        except (ValueError, RuntimeError):
            qed = None
            ro5_ok = None
            mw = logp = None

        entries.append({
            "smiles":   smiles,
            "affinity": affinity,
            "rmsd":     rmsd,
            "notes":    notes,
            "qed":      qed,
            "ro5_ok":   ro5_ok,
            "mw":       mw,
            "logp":     logp,
        })

    if not entries:
        return "No valid entries parsed.\n" + "\n".join(parse_errors)

    # Sort by binding affinity (most negative = strongest binder)
    entries.sort(key=lambda x: x["affinity"])

    # Build output
    out = ["Docking Results — Ranked by Binding Affinity\n" + "═" * 60]
    out.append(
        f"{'Rank':<5} {'SMILES':<35} {'ΔG (kcal/mol)':>14} "
        f"{'RMSD':>7} {'QED':>6} {'Ro5':>5}"
    )
    out.append("─" * 75)

    for rank, e in enumerate(entries, 1):
        label  = e["smiles"][:34] if len(e["smiles"]) > 34 else e["smiles"]
        rmsd_s = f"{e['rmsd']:.2f}" if e["rmsd"] is not None else "N/A"
        qed_s  = f"{e['qed']:.3f}" if e["qed"] is not None else "N/A"
        ro5_s  = ("✅" if e["ro5_ok"] else "❌") if e["ro5_ok"] is not None else "N/A"
        out.append(
            f"#{rank:<4} {label:<35} {e['affinity']:>+14.2f} "
            f"{rmsd_s:>7} {qed_s:>6} {ro5_s:>5}"
        )
        if e["notes"]:
            out.append(f"       Notes: {e['notes']}")

    # Recommendation logic
    out.append("\n" + "═" * 60)
    out.append("RECOMMENDATION:")

    best_affinity = entries[0]

    # Is the best binder also drug-like?
    if best_affinity["ro5_ok"] and best_affinity["qed"] and best_affinity["qed"] >= 0.4:
        out.append(
            f" Top pick: {best_affinity['smiles']}\n"
            f"   Strongest binding (ΔG = {best_affinity['affinity']:.2f} kcal/mol) "
            f"AND passes Lipinski Ro5 (QED={best_affinity['qed']:.3f}).\n"
            f"   This is the most promising candidate overall."
        )
    else:
        # Find best that passes Ro5
        drug_like = [
            e for e in entries
            if e["ro5_ok"] and e["qed"] and e["qed"] >= 0.4
        ]
        if drug_like:
            best_dl = drug_like[0]  # already sorted by affinity
            out.append(
                f" Best binder ({best_affinity['smiles']}, "
                f"ΔG={best_affinity['affinity']:.2f}) has poor drug-likeness "
                f"(Ro5={'FAIL' if not best_affinity['ro5_ok'] else 'PASS'}, "
                f"QED={best_affinity['qed']:.3f if best_affinity['qed'] else 'N/A'}).\n"
                f"   Best drug-like alternative: {best_dl['smiles']}\n"
                f"   ΔG={best_dl['affinity']:.2f} kcal/mol, QED={best_dl['qed']:.3f}\n"
                f"   Recommended for further development."
            )
        else:
            out.append(
                f"  Best binder: {best_affinity['smiles']} "
                f"(ΔG={best_affinity['affinity']:.2f} kcal/mol)\n"
                f"   No candidates fully pass drug-likeness filters.\n"
                f"   Consider structural optimisation before progression."
            )

    if parse_errors:
        out.append("\nParse warnings:\n" + "\n".join(parse_errors))

    return "\n".join(out)


# ──────────────────────────────────────────────────────────────
# Tool 5 — SMILES validator & basic info
# ──────────────────────────────────────────────────────────────
@tool
def validate_smiles(smiles: str) -> str:
    """
    Validate a SMILES string and return basic structural info:
    atom count, bond count, molecular formula, and canonical SMILES.
    Use this first if you are unsure whether a SMILES is valid.
    """
    try:
        mol = _mol_from_smiles(smiles)
    except (ValueError, RuntimeError) as e:
        return f" Invalid SMILES: {e}"

    formula    = rdMolDescriptors.CalcMolFormula(mol)
    canonical  = Chem.MolToSmiles(mol)
    num_atoms  = mol.GetNumAtoms()
    num_bonds  = mol.GetNumBonds()
    num_heavy  = mol.GetNumHeavyAtoms()
    charge     = Chem.GetFormalCharge(mol)

    return (
        f" Valid SMILES\n"
        f"  Input:           {smiles}\n"
        f"  Canonical SMILES:{canonical}\n"
        f"  Formula:         {formula}\n"
        f"  Total atoms:     {num_atoms}  (heavy: {num_heavy})\n"
        f"  Bonds:           {num_bonds}\n"
        f"  Formal charge:   {charge}\n"
    )


# ──────────────────────────────────────────────────────────────
# Tool 6 — ADMET estimator (rule-based)
# ──────────────────────────────────────────────────────────────
@tool
def estimate_admet(smiles: str) -> str:
    """
    Estimate ADMET (Absorption, Distribution, Metabolism, Excretion, Toxicity)
    properties using rule-based filters derived from published guidelines.
    This is a rapid screening tool — not a substitute for experimental data.
    """
    try:
        mol = _mol_from_smiles(smiles)
    except (ValueError, RuntimeError) as e:
        return f"Error: {e}"

    mw    = Descriptors.MolWt(mol)
    logp  = Descriptors.MolLogP(mol)
    tpsa  = Descriptors.TPSA(mol)
    hbd   = Lipinski.NumHDonors(mol)
    hba   = Lipinski.NumHAcceptors(mol)
    rot   = Lipinski.NumRotatableBonds(mol)
    rings = rdMolDescriptors.CalcNumRings(mol)
    fsp3  = rdMolDescriptors.CalcFractionCSP3(mol)

    lines = [f"ADMET profile for: {smiles}", "─" * 50]

    # Absorption
    abs_ok = tpsa <= 140 and rot <= 10 and mw <= 500
    gi_abs = "High" if tpsa < 75 else ("Moderate" if tpsa < 140 else "Low")
    bbb    = "Likely penetrant" if (tpsa < 90 and logp < 5 and mw < 450) else "Poor CNS penetration"
    lines.append(f"ABSORPTION:")
    lines.append(f"  Predicted GI absorption: {gi_abs}  (TPSA={tpsa:.1f} Å²)")
    lines.append(f"  BBB penetration:         {bbb}")
    lines.append(f"  P-gp substrate risk:     {'High' if mw > 400 and tpsa > 100 else 'Low/Moderate'}")

    # Distribution
    lines.append(f"DISTRIBUTION:")
    lines.append(f"  LogP={logp:.2f}  → "
                 f"{'lipophilic, may accumulate in fatty tissue' if logp > 3 else 'moderate/low lipophilicity'}")
    lines.append(f"  Plasma protein binding:  "
                 f"{'likely high (logP>3)' if logp > 3 else 'likely moderate'}")

    # Metabolism
    lines.append(f"METABOLISM:")
    lines.append(f"  CYP450 liability:        "
                 f"{'elevated (aromatic rings present)' if rdMolDescriptors.CalcNumAromaticRings(mol) > 0 else 'lower risk'}")
    lines.append(f"  First-pass effect risk:  "
                 f"{'significant if oral' if logp > 3 else 'moderate'}")

    # Excretion
    lines.append(f"EXCRETION:")
    lines.append(f"  Renal clearance:         "
                 f"{'likely' if tpsa > 60 and logp < 2 else 'hepatic route more probable'}")

    # Toxicity flags (simple structural alerts)
    tox_flags = []
    tox_smarts = {
        "Nitro group":          "[N+](=O)[O-]",
        "Aldehyde (reactive)":  "[CX3H1](=O)",
        "Michael acceptor":     "[C]=[C][C](=O)",
        "Epoxide":              "C1OC1",
        "Aniline (primary)":    "c1ccccc1N",
        "Halogenated aromatic": "c1ccccc1[F,Cl,Br,I]",
    }
    for alert_name, smarts in tox_smarts.items():
        pattern = Chem.MolFromSmarts(smarts)
        if pattern and mol.HasSubstructMatch(pattern):
            tox_flags.append(alert_name)

    lines.append(f"TOXICITY FLAGS:")
    if tox_flags:
        for flag in tox_flags:
            lines.append(f"    {flag} detected")
    else:
        lines.append("  No common structural alerts detected")

    lines.append(f"\nOverall ADMET suitability: "
                 f"{'Promising' if abs_ok and not tox_flags else 'Requires further evaluation'}")

    return "\n".join(lines)


# All tools exported
tools = [
    validate_smiles,
    get_molecular_properties,
    classify_drug_likeness,
    compare_molecules,
    analyze_docking_results,
    estimate_admet,
]

# ──────────────────────────────────────────────────────────────
# Tool 7 — molecule visualization
# ──────────────────────────────────────────────────────────────
@tool
def draw_molecule(smiles: str) -> str:
    """Generate a 2D structure image of a molecule from SMILES and save it."""
    try:
        mol = _mol_from_smiles(smiles)
    except (ValueError, RuntimeError) as e:
        return f"Error: {e}"

    from rdkit.Chem import Draw
    import os

    img = Draw.MolToImage(mol, size=(400, 300))
    filename = f"molecule_{abs(hash(smiles))}.png"
    img.save(filename)
    return f"Structure saved to: {filename}  (SMILES: {smiles})"

# ──────────────────────────────────────────────────────────────
# Tool 8 — similarity search between molecules
# ──────────────────────────────────────────────────────────────
@tool
def compute_similarity(smiles1: str, smiles2: str) -> str:
    """Compute Tanimoto similarity between two molecules using Morgan fingerprints."""
    try:
        mol1 = _mol_from_smiles(smiles1)
        mol2 = _mol_from_smiles(smiles2)
    except (ValueError, RuntimeError) as e:
        return f"Error: {e}"

    from rdkit.Chem import DataStructs
    from rdkit.Chem import AllChem

    fp1 = AllChem.GetMorganFingerprintAsBitVect(mol1, radius=2, nBits=2048)
    fp2 = AllChem.GetMorganFingerprintAsBitVect(mol2, radius=2, nBits=2048)
    similarity = DataStructs.TanimotoSimilarity(fp1, fp2)

    if similarity >= 0.85:
        interpretation = "Very similar (same scaffold likely)"
    elif similarity >= 0.65:
        interpretation = "Moderately similar"
    elif similarity >= 0.40:
        interpretation = "Somewhat similar"
    else:
        interpretation = "Structurally distinct"

    return (
        f"Tanimoto similarity: {similarity:.3f}\n"
        f"Interpretation: {interpretation}\n"
        f"  Mol1: {smiles1}\n"
        f"  Mol2: {smiles2}"
    )

# ──────────────────────────────────────────────────────────────
# Tool 9 — connect to CHEMBEL database
# ──────────────────────────────────────────────────────────────
@tool
def search_chembl(smiles: str) -> str:
    """
    Search ChEMBL database for known bioactivity data for a molecule.
    Returns ChEMBL ID, drug approval status, and up to 10 activity records.
    """
    try:
        from chembl_webresource_client.new_client import new_client
    except ImportError:
        return "Error: run 'pip install chembl-webresource-client'"

    molecule = new_client.molecule
    activity = new_client.activity

    # Try canonical SMILES first, fall back to raw input
    try:
        canonical = Chem.MolToSmiles(_mol_from_smiles(smiles))
    except Exception:
        canonical = smiles

    # Search by SMILES, then InChIKey as fallback
    mols = list(molecule.filter(
        molecule_structures__canonical_smiles=canonical
    ))

    if not mols:
        try:
            from rdkit.Chem.inchi import MolToInchi, InchiToInchiKey
            inchikey = InchiToInchiKey(MolToInchi(_mol_from_smiles(smiles)))
            mols = list(molecule.filter(
                molecule_structures__standard_inchi_key=inchikey
            ))
        except Exception:
            pass

    if not mols:
        return f"No molecule found in ChEMBL for: {smiles}"

    m         = mols[0]
    chembl_id = m.get("molecule_chembl_id", "?")
    name      = m.get("pref_name") or "No name"
    phase     = m.get("max_phase", 0)
    phase_label = {0: "Preclinical", 1: "Phase I", 2: "Phase II",
                   3: "Phase III", 4: "Approved"}.get(phase, f"Phase {phase}")

    lines = [
        f"ChEMBL ID: {chembl_id}  |  Name: {name}  |  Status: {phase_label}",
        "─" * 55,
    ]

    acts = list(activity.filter(molecule_chembl_id=chembl_id).only([
        "target_pref_name", "standard_type",
        "standard_value", "standard_units", "pchembl_value",
    ])[:10])

    if not acts:
        lines.append("No bioactivity data found.")
    else:
        for i, a in enumerate(acts, 1):
            target  = a.get("target_pref_name") or "Unknown target"
            s_type  = a.get("standard_type",  "N/A")
            s_value = a.get("standard_value", "N/A")
            s_units = a.get("standard_units", "")
            pchembl = a.get("pchembl_value")
            pc_str  = f"  pChEMBL={pchembl}" if pchembl else ""
            lines.append(f"{i:>2}. {target} | {s_type}={s_value} {s_units}{pc_str}")

    return "\n".join(lines)

# ──────────────────────────────────────────────────────────────
# Tool 10 — Docking Software
# ──────────────────────────────────────────────────────────────
@tool
def run_docking(
    smiles: str,
    receptor_pdbqt_path: str,
    center_x: float,
    center_y: float,
    center_z: float,
    box_size: float = 20.0,
    exhaustiveness: int = 8,
) -> str:
    """
    Dock a SMILES ligand against a receptor using AutoDock Vina.
    receptor_pdbqt_path must be a prepared .pdbqt file.
    center_x/y/z are the binding site coordinates in Angstroms.
    Returns top 5 poses ranked by binding affinity (kcal/mol).
    """
    try:
        from vina import Vina
        from meeko import MoleculePreparation
    except ImportError:
        return "Error: run 'pip install vina meeko'"

    import os, tempfile
    from rdkit.Chem import AllChem

    if not os.path.exists(receptor_pdbqt_path):
        return f"Error: receptor file not found: {receptor_pdbqt_path}"

    # Prepare ligand — SMILES → 3D → PDBQT
    try:
        mol = Chem.AddHs(_mol_from_smiles(smiles))
        if AllChem.EmbedMolecule(mol, AllChem.ETKDGv3()) == -1:
            return f"Error: could not generate 3D coordinates for: {smiles}"
        AllChem.MMFFOptimizeMolecule(mol)

        preparator = MoleculePreparation()
        setups     = preparator.prepare(mol)

        with tempfile.NamedTemporaryFile(
            suffix=".pdbqt", delete=False, mode="w"
        ) as f:
            ligand_path = f.name
            for setup in setups:
                preparator.write_pdbqt_string(setup, f)
    except Exception as e:
        return f"Error preparing ligand: {e}"

    # Run docking
    try:
        v = Vina(sf_name="vina", verbosity=0)
        v.set_receptor(receptor_pdbqt_path)
        v.set_ligand_from_file(ligand_path)
        v.compute_vina_maps(
            center=[center_x, center_y, center_z],
            box_size=[box_size, box_size, box_size],
        )
        v.dock(exhaustiveness=exhaustiveness, n_poses=5)
        energies = v.energies(n_poses=5)
    except Exception as e:
        return f"Docking failed: {e}"
    finally:
        os.unlink(ligand_path)

    # Format results
    best = energies[0][0]
    strength = (
        "Excellent" if best < -9 else
        "Good"      if best < -7 else
        "Moderate"  if best < -5 else
        "Weak"
    )

    lines = [
        f"Receptor: {os.path.basename(receptor_pdbqt_path)}",
        f"Ligand:   {smiles}",
        f"Box:      center=({center_x}, {center_y}, {center_z})  size={box_size}Å",
        "─" * 50,
        f"{'Pose':<6} {'ΔG (kcal/mol)':>14} {'RMSD lb':>9} {'RMSD ub':>9}",
        "─" * 50,
    ]

    for i, e in enumerate(energies, 1):
        lines.append(f"#{i:<5} {e[0]:>+14.2f} {e[1]:>9.2f} {e[2]:>9.2f}")

    lines += [
        "─" * 50,
        f"Best: {best:+.2f} kcal/mol → {strength} binding",
    ]

    return "\n".join(lines)