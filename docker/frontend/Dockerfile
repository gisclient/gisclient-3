# syntax=docker/dockerfile:1
FROM almalinux/8-base AS source

COPY ./public/ /app/author/public
RUN find /app \( -name "*.sql" -o -name "*.txt" -o -name "*.sh" -o -name ".gitignore" \) -type f -delete \
	&& find /app -type d -empty -delete


FROM almalinux/8-base AS prod

ARG OCI_VERSION
ARG OCI_REVISION
ARG OCI_SOURCE

LABEL org.opencontainers.image.version=$OCI_VERSION \
	org.opencontainers.image.revision=$OCI_REVISION \
	org.opencontainers.image.source=$OCI_SOURCE

WORKDIR /app

RUN dnf --nodocs update -y \
	&& dnf --nodocs install -y httpd \
	&& dnf clean all

COPY --from=source --chown=root:root /app /app
COPY --chown=root:root version.txt /app/author/version.txt
COPY --chown=root:root --chmod=744 docker/frontend/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/frontend/httpd.conf /etc/httpd/conf/httpd.conf
COPY docker/frontend/location.conf docker/frontend/php.conf /etc/httpd/conf.d/

ENV APACHE_SERVER_PORT "8080"
ENV APACHE_SERVER_DOCUMENT_ROOT "/app/author/public"
ENV APACHE_PHPFPM_HANDLER "proxy:fcgi://author-be:9000"

EXPOSE $APACHE_SERVER_PORT

# https://httpd.apache.org/docs/2.4/stopping.html#gracefulstop
STOPSIGNAL SIGWINCH

HEALTHCHECK --interval=60s --timeout=5s \
	CMD curl --silent --fail http://localhost:$APACHE_SERVER_PORT/ || exit 1

CMD ["docker-entrypoint.sh"]

FROM prod AS dev

# Add some development tools
RUN dnf --nodocs update -y \
	&& dnf --nodocs install -y binutils curl gzip procps telnet vim \
	&& dnf clean all

# No healthcheck in dev
HEALTHCHECK NONE
