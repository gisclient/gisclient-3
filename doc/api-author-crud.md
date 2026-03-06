# Author CRUD JSON API (Pilot)

This document describes the current JSON:API flow in the Author backend and the
components involved from controller to database.

Pilot scope:

- `project`

## Endpoints and auth

Base URL (docker default): `http://localhost:8080/api`

CRUD endpoints:

- `GET /api/{entity}`
- `GET /api/{entity}/{id}`
- `POST /api/{entity}`
- `PUT /api/{entity}/{id}`
- `DELETE /api/{entity}/{id}`

All `/api/*` endpoints require:

- authenticated session
- admin role

List query params:

- `limit` default `50`, max `200`
- `offset` default `0`
- `sort` e.g. `project_name` or `-project_name`
- `filter[field]=value`

## Component architecture

```mermaid
graph TD
    Client[API Client] --> Router[Symfony Router<br/>config/routing.yml]
    Router --> Controller[JsonApiController]

    Controller --> Service[ApiCrudService]
    Controller --> Mapper[JsonApiExceptionMapper]

    Service --> Auth[GCApp Authentication Handler<br/>isAuthenticated + isAdmin]
    Service --> DefProvider[EntityDefinitionProviderInterface]
    Service --> Validator[PayloadValidator]
    Service --> RepoIf[AuthorEntityRepositoryInterface]

    DefProvider --> CacheProvider[CachedEntityDefinitionProvider]
    CacheProvider --> TabVisibility[TabVisibilityEntityDefinitionProvider]
    TabVisibility --> InfoSchema[InformationSchemaEntityDefinitionProvider]
    TabVisibility --> TabFields[TabFieldExtractor]
    InfoSchema --> Registry[EntityRegistry<br/>allowed API entities]
    InfoSchema --> ISDB[information_schema queries]

    RepoIf --> Repo[PdoAuthorEntityRepository]
    Validator --> LookupCheck[FK lookup checks using DB]

    Repo --> DB[(PostgreSQL)]
    LookupCheck --> DB
```

## Request flow (controller to database)

```mermaid
flowchart TD
    A[HTTP request /api/entity] --> B[JsonApiController action]
    B --> C{Need JSON body?}

    C -- yes --> D[decodeJsonBody]
    D -- invalid --> E[ApiException 400 invalid_json]
    D -- ok --> F[ApiCrudService method]
    C -- no --> F

    F --> G[assertAdmin]
    G -- fail --> H[ApiException 401/403]
    G -- ok --> I[Load EntityDefinition]

    I --> J[InformationSchemaEntityDefinitionProvider<br/>columns, PK, FK metadata]
    J --> K[TabVisibilityEntityDefinitionProvider<br/>filter/order by TAB fields]
    K --> L[CachedEntityDefinitionProvider]

    L --> M{Operation}
    M -- list/show --> N[Repository findAll/findById]
    M -- create/update --> O[PayloadValidator]
    M -- delete --> P[Repository delete]

    O --> O1[validate payload shape and type]
    O --> O2[validate writable/required fields]
    O --> O3[validate attribute types]
    O --> O4[validate FK-based allowed values]
    O -- errors --> Q[ValidationException 422]
    O -- ok --> R[Repository create/update]

    N --> DB[(PostgreSQL)]
    P --> DB
    R --> DB

    DB --> S{PDO exception?}
    S -- no --> T[resource payload data/meta]
    S -- yes --> U[Repository SQLSTATE mapping]
    U --> U1[409 unique_constraint_violation]
    U --> U2[409 foreign_key_violation]
    U --> U3[422 invalid_attribute_value]
    U --> U4[500 database_error]

    E --> V[JsonApiExceptionMapper]
    H --> V
    Q --> V
    U1 --> V
    U2 --> V
    U3 --> V
    U4 --> V
    T --> W[JSON response]
    V --> W
```

## Payload notes (project)

Example create payload:

```json
{
  "data": {
    "type": "project",
    "id": "milano",
    "attributes": {
      "project_title": "Milano",
      "xc": 501090,
      "yc": 5022596,
      "project_srid": 32632,
      "max_extent_scale": 50000,
      "charset_encodings_id": 2,
      "default_language_id": "it",
      "project_note": null
    }
  }
}
```

`data.id` maps to `project_name`. If both `data.id` and
`data.attributes.project_name` are provided, values must match.

`PUT` is full replace over writable fields for the entity (missing writable
fields are normalized to `null` by validator logic).

## Current limitations

- Relationships endpoints are not exposed in this pilot.
- Mapfile refresh is not automatically triggered by CRUD writes.
