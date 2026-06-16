FROM python:3.11-slim

# Set working directory
WORKDIR /app

# Install system dependencies (including X11 libs for RDKit image generation)
RUN apt-get update && apt-get install -y \
    build-essential \
    gcc \
    g++ \
    cmake \
    libxrender1 \
    libxext6 \
    libsm6 \
    libgomp1 \
    libxkbcommon0 \
    libdbus-1-3 \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements first for better caching
COPY requirements.txt .

# Install Python dependencies
RUN pip install --no-cache-dir -r requirements.txt

# Copy application code
COPY . .

EXPOSE 7860

# Create directory for images if it doesn't exist
RUN mkdir -p app/static/images && chmod 777 app/static/images

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD curl -f http://localhost:7860/health || exit 1

# Set Python to run in unbuffered mode
ENV PYTHONUNBUFFERED=1

CMD python -m uvicorn app.main:app --host 0.0.0.0 --port ${API_PORT:-7860} --workers 2
