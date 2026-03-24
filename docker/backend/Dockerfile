# syntax=docker/dockerfile:1

FROM almalinux/8-base AS source

COPY ./ /app/author/

RUN rm -rf /app/author/docker \
	&& find /app -type d -empty -delete


FROM almalinux/8-base AS base

WORKDIR /app

RUN dnf module enable -y php:7.4 \
	&& dnf --nodocs install -y \
	php \
	php-cli \
	php-json \
	php-posix \
	php-tokenizer \
	php-gettext \
	php-bcmath \
	php-fpm \
	php-gd \
	php-intl \
	php-opcache \
	php-openssl \
	php-pdo \
	php-pear \
	php-pgsql \
	php-pcntl \
	php-sockets \
	php-xml \
	php-zip \
	&& mkdir -p /run/php-fpm \ 
	&& dnf clean all


FROM base AS builder

ARG TARGETARCH

RUN case "${TARGETARCH}" in \
		amd64) rpm_arch="x86_64" ;; \
		arm64) rpm_arch="aarch64" ;; \
		*) echo "Unsupported TARGETARCH: ${TARGETARCH}" >&2; exit 1 ;; \
	esac \
	&& dnf install -y dnf-utils \
	&& dnf install -y epel-release \
	&& dnf config-manager --set-enabled powertools \
	&& dnf -qy module disable postgresql \
	&& dnf install -y "https://download.postgresql.org/pub/repos/yum/reporpms/EL-8-${rpm_arch}/pgdg-redhat-repo-latest.noarch.rpm" \
	&& dnf -qy module reset php \
	&& dnf -qy module enable php:7.4 \
	&& dnf install -y git rpm-build rpmdevtools yum-utils perl libpng-devel freetype-devel libxml2-devel libpq5 giflib-devel fcgi-devel cairo-devel harfbuzz-devel exempi-devel cmake make gcc gcc-c++ libXpm-devel readline-devel librsvg2-devel httpd-devel libzip php-devel libxslt-devel pam-devel fcgi-devel mysql-devel swig freetype-devel gd-devel python3 python3-devel curl-devel zlib-devel libxml2-devel libjpeg-devel libtiff-devel fribidi-devel harfbuzz-devel protobuf-c-devel libstdc++-devel \
	&& dnf install -y postgresql16-devel gdal36-devel geos312-devel proj92-devel postgis34_16 \
	&& ldconfig \
	&& dnf clean all

COPY docker/backend/mapserver-7.6.5_postgres16.spec /app/redhat/mapserver-7.6.5_postgres16.spec
COPY docker/backend/mapserver-7.6.5_r3gis_role.patch /app/redhat/mapserver-7.6.5_r3gis_role.patch

RUN case "${TARGETARCH}" in \
		amd64) rpm_arch="x86_64" ;; \
		arm64) rpm_arch="aarch64" ;; \
		*) echo "Unsupported TARGETARCH: ${TARGETARCH}" >&2; exit 1 ;; \
	esac \
	&& bash -c "mkdir -p rpmbuild/{BUILD,RPMS,SOURCES,SPECS,SRPMS}" && \
	cp "$(pwd)/redhat/mapserver-7.6.5_r3gis_role.patch" "$(pwd)/rpmbuild/SOURCES/." && \
	spectool -g -C "$(pwd)/rpmbuild/SOURCES" "$(pwd)/redhat/mapserver-7.6.5_postgres16.spec" && \
	rpmbuild -D "_topdir $(pwd)/rpmbuild" --noclean --target "${rpm_arch}" -ba "$(pwd)/redhat/mapserver-7.6.5_postgres16.spec" \
	&& mkdir -p /tmp/rpms \
	&& cp "$(pwd)/rpmbuild/RPMS/${rpm_arch}/mapserver-7.6.5-4.el8.${rpm_arch}.rpm" /tmp/rpms/mapserver.rpm \
	&& cp "$(pwd)/rpmbuild/RPMS/${rpm_arch}/php-mapserver-7.6.5-4.el8.${rpm_arch}.rpm" /tmp/rpms/php-mapserver.rpm


FROM base AS mapserver-rls

ARG TARGETARCH

RUN --mount=type=bind,from=builder,source=/tmp/rpms/mapserver.rpm,target=/tmp/mapserver.rpm,readonly \
	--mount=type=bind,from=builder,source=/tmp/rpms/php-mapserver.rpm,target=/tmp/php-mapserver.rpm,readonly \
	--mount=type=bind,source=docker/backend/docker-entrypoint.sh,target=/tmp/docker-entrypoint.sh,readonly \
	--mount=type=bind,source=docker/backend/php-fpm.conf,target=/tmp/php-fpm.conf,readonly \
	--mount=type=bind,source=docker/backend/fop.r3-gis.conf,target=/tmp/fop.r3-gis.conf,readonly \
	case "${TARGETARCH}" in \
		amd64) rpm_arch="x86_64" ;; \
		arm64) rpm_arch="aarch64" ;; \
		*) echo "Unsupported TARGETARCH: ${TARGETARCH}" >&2; exit 1 ;; \
	esac \
	&& test -f /tmp/mapserver.rpm \
	&& test -f /tmp/php-mapserver.rpm \
	&& curl https://archive.apache.org/dist/xmlgraphics/fop/binaries/fop-1.1-bin.tar.gz -o /tmp/fop-1.1-bin.tar.gz \
	&& cp /tmp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh \
	&& chmod +x /usr/local/bin/docker-entrypoint.sh \
	&& cp /tmp/php-fpm.conf /etc/php-fpm.conf \
	&& dnf install --nodocs -qy epel-release dnf-utils \
	&& dnf config-manager --set-enabled powertools \
	&& dnf -qy module disable postgresql \
	&& dnf install -qy "https://download.postgresql.org/pub/repos/yum/reporpms/EL-8-${rpm_arch}/pgdg-redhat-repo-latest.noarch.rpm" \
	&& dnf -qy module reset php \
	&& dnf -qy module enable php:7.4 \
	&& dnf install --nodocs -qy cairo exempi fcgi fribidi gdal36 geos312 giflib harfbuzz proj92 protobuf-c librsvg2 \
	&& rpm -i /tmp/mapserver.rpm \
	&& rpm -i /tmp/php-mapserver.rpm \
	&& dnf install --nodocs -qy java-1.8.0-openjdk \
	&& tar xzf /tmp/fop-1.1-bin.tar.gz -C /usr/local/ \
	&& ln -s /usr/local/fop-1.1 /usr/local/fop \
	&& cp /tmp/fop-1.1-bin.tar.gz /usr/local/fop/conf/fop.r3-gis.conf \
	&& mkdir /var/www/.fop \
	&& chmod g+ws /var/www/.fop \
	&& chown root:apache /var/www/.fop \
	&& sed -i -E "s/(^fop_exec_command=.*\$)/fop_exec_command=\"exec \\\\\"\$JAVACMD\\\\\" -Xmx1024m \$java_exec_args \$LOGCHOICE \$LOGLEVEL -classpath \\\\\"\$LOCALCLASSPATH\\\\\" \$FOP_OPTS org.apache.fop.cli.Main \$fop_exec_args\\\"/" /usr/local/fop/fop \
	&& rm -f /tmp/fop-1.1-bin.tar.gz \
	&& dnf clean all


FROM mapserver-rls AS dev

USER 0
ARG UID=48
ARG GID=48
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=source --chown=root:root /app /app
RUN dnf --nodocs update -y \
	&& dnf --nodocs install -y binutils curl gzip procps telnet unzip vim \
	&& dnf clean all \
	&& cd /app/author \
	&& composer install --no-ansi --no-interaction --no-plugins --no-progress --optimize-autoloader \
	&& chmod 777 /app/author/var \
	&& chmod 777 /app/author/map \
	&& cp -a /app/author/fonts/fonts_sample.list /app/author/fonts/fonts.list \
	&& rm -rf /root/.cache/composer
WORKDIR /app/author
USER ${UID}
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["-F"]
STOPSIGNAL SIGQUIT
EXPOSE 9000
HEALTHCHECK NONE


FROM mapserver-rls AS prod

ARG OCI_VERSION
ARG OCI_REVISION
ARG OCI_SOURCE

LABEL org.opencontainers.image.version=$OCI_VERSION \
	org.opencontainers.image.revision=$OCI_REVISION \
	org.opencontainers.image.source=$OCI_SOURCE

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
COPY --from=source --chown=root:root /app /app
COPY --chown=root:root version.txt /app/author/version.txt

RUN cd /app/author \
	&& composer install --no-ansi --no-dev --no-interaction --no-plugins --no-progress --optimize-autoloader \
	&& chmod 777 /app/author/var \
	&& chmod 777 /app/author/map \
	&& cp -a /app/author/fonts/fonts_sample.list /app/author/fonts/fonts.list \
	&& rm -rf /root/.cache/composer

USER 48
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["-F"]
STOPSIGNAL SIGQUIT
EXPOSE 9000
HEALTHCHECK NONE
