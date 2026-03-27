# Product Service

Symfony 6.4 microservice responsible for managing products. Exposes a REST API for creating and reading products, and publishes product events to RabbitMQ so downstream services (e.g. order-service) can keep a local product copy in sync.

Part of a two-service stack. See also: [order-service](../order-service/README.md) and [shared-bundle](../shared-bundle).

---

## Architecture

```
HTTP Client
    │
    ▼
 Nginx :8080
    │
    ▼
 PHP-FPM (Symfony 6.4)
    │
    ├─► PostgreSQL :5432  (product_db)
    │
    └─► RabbitMQ :5672   (products – fanout exchange)
            │
            └─► order-service consumer (via shared microservices_net network)
```

### Key components

| Class | Role |
|---|---|
| `ProductController` | REST endpoints |
| `ProductService` | Business logic, dispatches `ProductMessage` |
| `Product` (entity) | Extends `AbstractProduct` from shared bundle; adds UUID PK and timestamps |
| `CreateProductRequest` (DTO) | Input validation via Symfony Validator constraints |
| `ProductMessage` | Shared message class from `microservices/shared-bundle` |

---

## Requirements

- Docker + Docker Compose v2

No local PHP or Composer installation needed — everything runs inside containers.

---

## Getting started

### 1. Start product-service first

Product-service owns the shared RabbitMQ instance and creates the `microservices_net` Docker network that order-service joins.

```bash
docker compose up -d --build
```

### 2. Run database migrations

```bash
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### 3. Verify

```bash
curl http://localhost:8080/products
# {"data":[]}
```

---

## Makefile shortcuts

```bash
make up        # docker compose up -d
make down      # docker compose down
make build     # rebuild images without cache
make bash      # open shell inside php container
make migrate   # run pending migrations
make cc        # clear Symfony cache
make stack-up  # start product-service + order-service together
make stack-down
```

---

## API reference

### `POST /products` — Create a product

**Request body**

```json
{
    "name": "Coffee Mug",
    "price": 12.99,
    "quantity": 100
}
```

| Field | Type | Rules |
|---|---|---|
| `name` | string | required, 1–255 chars |
| `price` | float | required, > 0 |
| `quantity` | integer | required, ≥ 0 |

**Response `201 Created`**

```json
{
    "id": "018e1234-abcd-7000-8000-000000000001",
    "name": "Coffee Mug",
    "price": 12.99,
    "quantity": 100
}
```

**Response `422 Unprocessable Entity`** (validation failure)

```json
{
    "errors": {
        "price": "Price must be greater than 0."
    }
}
```

---

### `GET /products` — List all products

**Response `200 OK`**

```json
{
    "data": [
        {
            "id": "018e1234-abcd-7000-8000-000000000001",
            "name": "Coffee Mug",
            "price": 12.99,
            "quantity": 100
        }
    ]
}
```

---

### `GET /products/{id}` — Get a single product

**Response `200 OK`**

```json
{
    "id": "018e1234-abcd-7000-8000-000000000001",
    "name": "Coffee Mug",
    "price": 12.99,
    "quantity": 100
}
```

**Response `404 Not Found`**

```json
{
    "error": "Product not found."
}
```

---

## RabbitMQ

Every successful `POST /products` dispatches a `ProductMessage` to the `products` fanout exchange.

| Property | Value |
|---|---|
| Exchange | `products` |
| Type | fanout |
| Queue | `products` |
| Serializer | `symfony_serializer` (JSON) |
| Message class | `Microservices\SharedBundle\Message\ProductMessage` |

The `ProductMessage` payload (as JSON in the AMQP body):

```json
{
    "id": "018e1234-abcd-7000-8000-000000000001",
    "name": "Coffee Mug",
    "price": 12.99,
    "quantity": 100
}
```

The AMQP message also carries a `type` header set to `Microservices\SharedBundle\Message\ProductMessage` which order-service uses to deserialize correctly.

Management UI: [http://localhost:15672](http://localhost:15672) (guest / guest)

---

## Environment variables

| Variable | Default | Description |
|---|---|---|
| `APP_ENV` | `dev` | Symfony environment |
| `APP_SECRET` | *(set in .env)* | Symfony secret |
| `DATABASE_URL` | `postgresql://app:secret@postgres:5432/product_db` | Doctrine DSN |
| `MESSENGER_TRANSPORT_DSN` | `amqp://guest:guest@rabbitmq:5672/%2f/messages` | RabbitMQ DSN |

Copy `.env` to `.env.local` to override values without touching the committed file.

---

## Docker services

| Service | Image | Port (host) |
|---|---|---|
| `nginx` | nginx:1.25-alpine | 8080 |
| `php` | php:8.2-fpm-alpine (custom) | — |
| `postgres` | postgres:16-alpine | 5432 |
| `rabbitmq` | rabbitmq:3.12-management-alpine | 5672, 15672 |

`php` and `nginx` mount the project root as a volume — code changes take effect immediately without rebuilding.

---

## Shared bundle

This service depends on [`microservices/shared-bundle`](../shared-bundle) for:

- `AbstractProduct` — Doctrine `MappedSuperclass` providing `name`, `price`, `quantity` columns
- `ProductMessage` — canonical AMQP message DTO
- Messenger transport configuration (exchange name, serializer, routing)

The bundle is resolved from the local `../shared-bundle` path repository. In a real deployment, replace the path repository in `composer.json` with a VCS or Satis entry and pin a version tag.

---

## Database schema

```
products
├── id          UUID (PK)
├── name        VARCHAR(255)
├── price       NUMERIC(10,2)
├── quantity    INTEGER
├── created_at  TIMESTAMP
└── updated_at  TIMESTAMP
```
