"""
MongoDB Client
==============
Initialises the Motor (async MongoDB) client and exposes a single
`get_database()` helper consumed by the repository layer.

The client is created once at module import time.
The FastAPI lifespan hook in main.py pings the server on startup
to validate connectivity before accepting requests.
"""

from motor.motor_asyncio import AsyncIOMotorClient, AsyncIOMotorDatabase

from app.core.config import settings
from app.core.logging import logger


_client: AsyncIOMotorClient | None = None


def get_client() -> AsyncIOMotorClient:
    """Return the shared Motor client, creating it on first call."""
    global _client
    if _client is None:
        _client = AsyncIOMotorClient(settings.MONGO_URI)
        logger.info("MongoDB client initialised: %s", settings.MONGO_URI)
    return _client


def get_database() -> AsyncIOMotorDatabase:
    """Return the application database handle."""
    return get_client()[settings.database_name]


async def ping() -> bool:
    """
    Ping the MongoDB server.

    Returns True on success, False on connection failure.
    Called from FastAPI lifespan to fail fast on startup.
    """
    try:
        await get_client().admin.command("ping")
        logger.info("MongoDB ping successful.")
        return True
    except Exception as exc:  # noqa: BLE001
        logger.error("MongoDB ping failed: %s", exc)
        return False


async def close() -> None:
    """Close the Motor client connection pool (called on shutdown)."""
    global _client
    if _client is not None:
        _client.close()
        _client = None
        logger.info("MongoDB client closed.")
