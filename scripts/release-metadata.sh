#!/bin/sh

set -eu

SQL_FILE=${SQL_FILE:-$(ls doc/migrations/[0-9]*.sql 2>/dev/null | sort -V | tail -1)}
VERSION_FILE=${VERSION_FILE:-version.txt}
BUILD_NUMBER=${BUILD_NUMBER:-local}

usage() {
    cat <<'EOF'
Usage: scripts/release-metadata.sh <command>

Commands:
  parse-version      Print the application version parsed from the SQL file
  git-sha            Print the full git commit SHA
  source-url         Print the HTTPS repository URL
  versioned-tag      Print <parsed-version>-<build-number>
  version-file       Print <parsed-version>-<full-commit-hash>
  write-version-file Write version.txt in the repository root
EOF
}

parse_version() {
    awk '
    BEGIN {
        assigned = "";
        inserted = "";
        in_version_insert = 0;
        sq = sprintf("%c", 39);
    }
    {
        if (index($0, "v_author_version = " sq) > 0) {
            split($0, parts, sq);
            if (parts[2] ~ /^[0-9]+\.[0-9]+\.[0-9]+$/) {
                assigned = parts[2];
            }
        }

        lower = tolower($0);

        # Single-line INSERT: INSERT INTO [schema.]version ... VALUES (...) on one line
        if (lower ~ /insert into [^ ]*version/ && $0 ~ /'\''author'\''/) {
            if (index(lower, "values (" sq) > 0) {
                split($0, parts, sq);
                if (parts[2] ~ /^[0-9]+\.[0-9]+\.[0-9]+$/) {
                    inserted = parts[2];
                    in_version_insert = 0;
                }
            } else if (lower ~ /values \(v_author_version, '\''author'\''/) {
                if (assigned == "") {
                    print "Unable to resolve version: INSERT uses v_author_version before assignment" > "/dev/stderr";
                    exit 1;
                }
                inserted = assigned;
                in_version_insert = 0;
            }
        }

        # Multi-line INSERT: detect the INSERT line, then look for VALUES on next line
        if (lower ~ /insert into [^ ]*version/ && lower !~ /values /) {
            in_version_insert = 1;
        } else if (in_version_insert && index(lower, "values (" sq) > 0 && $0 ~ /'\''author'\''/) {
            split($0, parts, sq);
            if (parts[2] ~ /^[0-9]+\.[0-9]+\.[0-9]+$/) {
                inserted = parts[2];
            }
            in_version_insert = 0;
        } else if (in_version_insert && lower !~ /^[ \t]*$/) {
            in_version_insert = 0;
        }
    }
    END {
        if (inserted == "") {
            print "Unable to resolve version: no INSERT INTO version for author found" > "/dev/stderr";
            exit 1;
        }

        if (assigned != "" && assigned != inserted) {
            print "Unable to resolve version: assignment and insert results disagree (" assigned " vs " inserted ")" > "/dev/stderr";
            exit 1;
        }

        print inserted;
    }' "$SQL_FILE"
}

git_sha() {
    git rev-parse HEAD
}

source_url() {
    if [ -n "${OCI_SOURCE:-}" ]; then
        printf '%s\n' "$OCI_SOURCE"
        return
    fi

    remote_url=$(git remote get-url origin)

    case "$remote_url" in
        git@github.com:*)
            remote_url=${remote_url#git@github.com:}
            remote_url=${remote_url%.git}
            printf 'https://github.com/%s\n' "$remote_url"
            ;;
        https://github.com/*)
            remote_url=${remote_url%.git}
            printf '%s\n' "$remote_url"
            ;;
        *)
            printf 'Unable to derive GitHub source URL from remote: %s\n' "$remote_url" >&2
            exit 1
            ;;
    esac
}

versioned_tag() {
    version=$(parse_version)
    printf '%s-%s\n' "$version" "$BUILD_NUMBER"
}

version_file_value() {
    version=$(parse_version)
    sha=$(git_sha)
    printf '%s-%s-%s\n' "$version" "$BUILD_NUMBER" "$sha"
}

write_version_file() {
    version_file_value > "$VERSION_FILE"
    printf '%s\n' "$VERSION_FILE"
}

command=${1:-}

case "$command" in
    parse-version)
        parse_version
        ;;
    git-sha)
        git_sha
        ;;
    source-url)
        source_url
        ;;
    versioned-tag)
        versioned_tag
        ;;
    version-file)
        version_file_value
        ;;
    write-version-file)
        write_version_file
        ;;
    *)
        usage >&2
        exit 1
        ;;
esac
