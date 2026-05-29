# Multi-stage build for optimized image
FROM python:3.10-slim AS builder

# Install system dependencies for building
RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Create virtual environment
RUN python -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Copy requirements and install Python dependencies
COPY requirements.txt .
RUN pip install --no-cache-dir --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Install DeepPurpose from GitHub (not on PyPI)
RUN pip install --no-cache-dir git+https://github.com/Alantic/DeepPurpose.git

# Install TDC from GitHub (Therapeutic Data Commons)
RUN pip install --no-cache-dir git+https://github.com/Alantic/TDC.git

# Final stage
FROM python:3.10-slim

# Install runtime dependencies only
RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Copy virtual environment from builder
COPY --from=builder /opt/venv /opt/venv

# Verify packages were copied successfully
RUN /opt/venv/bin/pip list | grep -E 'DeepPurpose|TDC|torch' || echo "Warning: Some packages may not be present"

# Set environment variables
ENV PATH="/opt/venv/bin:$PATH" \
    PYTHONUNBUFFERED=1 \
    PYTHONDONTWRITEBYTECODE=1

# Create app directory
WORKDIR /app

# Copy application code
COPY . /app

# Remove any incomplete model artifacts from the repo copy, then download full weights
RUN rm -rf /app/save_folder

ARG DEEP_PURPOSE_MODEL=MPNN_CNN_BindingDB
RUN /opt/venv/bin/python -c "from DeepPurpose.utils import download_pretrained_model; import os; \
model_path = download_pretrained_model('${DEEP_PURPOSE_MODEL}', save_dir='/app/save_folder'); \
assert os.path.isfile(os.path.join(model_path, 'config.pkl')), 'config.pkl missing after download'; \
assert os.path.isfile(os.path.join(model_path, 'model.pt')), 'model.pt missing after download'"

# Create non-root user for security
RUN useradd -m -u 1000 appuser && chown -R appuser:appuser /app
USER appuser

# Expose port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=5 \
    CMD /opt/venv/bin/python -c "import urllib.request; urllib.request.urlopen('http://localhost:8000/health')"


# Run the application
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
