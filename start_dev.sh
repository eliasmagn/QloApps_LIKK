#!/usr/bin/env bash
set -euo pipefail

# Determine project root relative to this script
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VENV_DIR="$PROJECT_ROOT/.venv"

info() {
  printf '\e[34m[INFO]\e[0m %s\n' "$*"
}

warn() {
  printf '\e[33m[WARN]\e[0m %s\n' "$*" >&2
}

error() {
  printf '\e[31m[ERROR]\e[0m %s\n' "$*" >&2
}

ensure_python_venv() {
  if ! command -v python3 >/dev/null 2>&1; then
    error "python3 is required to create a virtual environment."
    exit 1
  fi

  if [[ ! -d "$VENV_DIR" ]]; then
    info "Creating Python virtual environment under $VENV_DIR"
    python3 -m venv "$VENV_DIR"
  else
    info "Using existing Python virtual environment at $VENV_DIR"
  fi

  # shellcheck disable=SC1090
  source "$VENV_DIR/bin/activate"
  python -m pip install --upgrade pip wheel setuptools >/dev/null

  local requirements_file="$PROJECT_ROOT/requirements-dev.txt"
  if [[ -f "$requirements_file" ]]; then
    info "Installing Python development dependencies from requirements-dev.txt"
    pip install -r "$requirements_file"
  fi
}

ensure_php_dependencies() {
  if command -v composer >/dev/null 2>&1; then
    info "Installing PHP dependencies with composer"
    composer install --no-interaction --prefer-dist --working-dir="$PROJECT_ROOT"
  else
    warn "Composer not found. Skipping PHP dependency installation. Install Composer to manage PHP packages."
  fi
}

check_application_config() {
  local settings_file="$PROJECT_ROOT/config/settings.inc.php"
  if [[ ! -f "$settings_file" ]]; then
    warn "config/settings.inc.php is missing. Run the installer via /install before using the application."
  fi
}

start_php_server() {
  local host="${HOST:-127.0.0.1}"
  local port="${PORT:-8000}"
  local router="$PROJECT_ROOT/tools/dev-router.php"

  if ! command -v php >/dev/null 2>&1; then
    error "php is required to run the development server."
    exit 1
  fi

  if [[ ! -f "$router" ]]; then
    error "Expected router script $router not found."
    exit 1
  fi

  info "Starting PHP development server at http://$host:$port"
  info "Press Ctrl+C to stop the server."
  cd "$PROJECT_ROOT"
  exec php -S "$host:$port" -t "$PROJECT_ROOT" "$router"
}

ensure_python_venv
ensure_php_dependencies
check_application_config
start_php_server
