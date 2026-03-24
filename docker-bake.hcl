variable "DEFAULT_REGISTRY" {
  default = "ghcr.io/gisclient"
}

variable "BACKEND_IMAGE" {
  default = "${DEFAULT_REGISTRY}/gisclient-3-backend"
}

variable "FRONTEND_IMAGE" {
  default = "${DEFAULT_REGISTRY}/gisclient-3-frontend"
}

variable "VERSIONED_TAG" {
  default = "local"
}

variable "GIT_SHA" {
  default = "unknown"
}

variable "OCI_SOURCE" {
  default = "https://github.com/gisclient/gisclient-3"
}

variable "VALIDATE_PLATFORMS" {
  default = "linux/amd64,linux/arm64"
}

variable "PUBLISH_PLATFORMS" {
  default = "linux/amd64,linux/arm64"
}

variable "BACKEND_CACHE_SCOPE" {
  default = "backend-prod-multiarch"
}

variable "FRONTEND_CACHE_SCOPE" {
  default = "frontend-prod-multiarch"
}

target "_common" {
  context = "."
  args = {
    OCI_VERSION = "${VERSIONED_TAG}"
    OCI_REVISION = "${GIT_SHA}"
    OCI_SOURCE = "${OCI_SOURCE}"
  }
}

target "backend" {
  inherits = ["_common"]
  dockerfile = "docker/backend/Dockerfile"
  target = "prod"
  pull = true
  tags = [
    "${BACKEND_IMAGE}:latest",
    "${BACKEND_IMAGE}:${VERSIONED_TAG}",
  ]
}

target "frontend" {
  inherits = ["_common"]
  dockerfile = "docker/frontend/Dockerfile"
  target = "prod"
  pull = true
  tags = [
    "${FRONTEND_IMAGE}:latest",
    "${FRONTEND_IMAGE}:${VERSIONED_TAG}",
  ]
}

target "backend-validate" {
  inherits = ["backend"]
  platforms = split(",", VALIDATE_PLATFORMS)
  cache-from = [
    "type=gha,scope=${BACKEND_CACHE_SCOPE}",
  ]
  cache-to = [
    "type=gha,scope=${BACKEND_CACHE_SCOPE},mode=max",
  ]
  output = ["type=cacheonly"]
}

target "frontend-validate" {
  inherits = ["frontend"]
  platforms = split(",", VALIDATE_PLATFORMS)
  cache-from = [
    "type=gha,scope=${FRONTEND_CACHE_SCOPE}",
  ]
  cache-to = [
    "type=gha,scope=${FRONTEND_CACHE_SCOPE},mode=max",
  ]
  output = ["type=cacheonly"]
}

target "backend-publish" {
  inherits = ["backend"]
  platforms = split(",", PUBLISH_PLATFORMS)
  cache-from = [
    "type=gha,scope=${BACKEND_CACHE_SCOPE}",
  ]
}

target "frontend-publish" {
  inherits = ["frontend"]
  platforms = split(",", PUBLISH_PLATFORMS)
  cache-from = [
    "type=gha,scope=${FRONTEND_CACHE_SCOPE}",
  ]
}

group "validate" {
  targets = ["backend-validate", "frontend-validate"]
}

group "publish" {
  targets = ["backend-publish", "frontend-publish"]
}
