FROM python:3.12-alpine@sha256:5789aa194f757888f41fa3d48e14b6feaa1b57c0af0b56fcb1372d6474d3bff0
ENV PYTHONDONTWRITEBYTECODE=1 PYTHONUNBUFFERED=1 FAP_STATE=/state/status.json
WORKDIR /app
COPY server.py ./
COPY app/ ./app/
USER 65534:65534
EXPOSE 8864
HEALTHCHECK --interval=30s --timeout=5s CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8864/api/status',timeout=3)"
CMD ["python","server.py","--host","0.0.0.0","--port","8864"]
