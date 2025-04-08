%define MS_REL %{nil}

Name:           mapserver%{MS_REL}
Version:        7.6.5
Release:        4%{?dist}
Summary:        Environment for building spatially-enabled internet applications

Group:          Development/Tools
License:        BSD
URL:            http://www.mapserver.org

Source0:        http://download.osgeo.org/mapserver/mapserver-%{version}.tar.gz
Patch0:         mapserver-%{version}_r3gis_role.patch
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

Requires:       httpd
Requires:       dejavu-sans-fonts

BuildRequires:  cmake make gcc gcc-c++
BuildRequires:  libXpm-devel readline-devel librsvg2-devel
BuildRequires:  swig php-devel libxslt-devel pam-devel fcgi-devel
BuildRequires:  postgresql16-devel postgis34_16
BuildRequires:  geos312-devel proj92-devel gdal36-devel cairo-devel
BuildRequires:  freetype-devel gd-devel >= 2.0.16
BuildRequires:  curl-devel zlib-devel libxml2-devel
BuildRequires:  libjpeg-devel libpng-devel libtiff-devel fribidi-devel giflib-devel
BuildRequires:  harfbuzz-devel protobuf-c-devel
BuildRequires:  libstdc++-devel


%description
Mapserver is an internet mapping program that converts GIS data to
map images in real time. With appropriate interface pages, 
Mapserver can provide an interactive internet map based on 
custom GIS data.

%package -n php-%{name}
Summary:        PHP/Mapscript map making extensions to PHP
Group:          Development/Languages
BuildRequires:  php-devel
Requires:       php-gd%{?_isa}
Requires:       php(zend-abi) = %{php_zend_api}
Requires:       php(api) = %{php_core_api}

%description -n php-%{name}
The PHP/Mapscript extension provides full map customization capabilities within
the PHP scripting language.

%prep
%setup -q -n mapserver-%{version}
%patch0 -p0

# replace fonts for tests with symlinks
rm -rf tests/vera/Vera.ttf
rm -rf tests/vera/VeraBd.ttf
pushd tests/vera/
ln -sf /usr/share/fonts/dejavu/DejaVuSans.ttf Vera.ttf
ln -sf /usr/share/fonts/dejavu/DejaVuSans-Bold.ttf VeraBd.ttf
popd

%build
mkdir build
pushd build

%cmake -DINSTALL_LIB_DIR=%{_libdir} \
       -DCMAKE_INSTALL_PREFIX=%{_prefix} \
       -DCMAKE_SKIP_RPATH=ON \
       -DCMAKE_CXX_FLAGS_RELEASE="%{optflags} -fno-strict-aliasing" \
       -DCMAKE_C_FLAGS_RELEASE="%{optflags} -fno-strict-aliasing" \
       -DCMAKE_VERBOSE_MAKEFILE=ON \
       -DCMAKE_BUILD_TYPE="Release" \
       -DCMAKE_SKIP_INSTALL_RPATH=ON \
       -DCMAKE_SKIP_RPATH=ON \
       -DWITH_CLIENT_WMS=ON \
       -DWITH_CLIENT_WFS=ON \
       -DWITH_CURL=ON \
       -DWITH_SOS=ON \
       -DWITH_PHP=ON \
       -DWITH_KML=ON \
       -DWITH_POSTGIS=ON \
       -DWITH_OGR=ON \
       -DWITH_PROJ=ON \
       -DWITH_RSVG=ON \
       -DWITH_KML=ON \
       -DWITH_FRIBIDI=ON \
       -DWITH_ICONV=ON \
       -DWITH_CAIRO=ON \
       -DWITH_FCGI=ON \
       -DWITH_GEOS=ON \
       -DWITH_WFS=ON \
       -DWITH_WCS=ON \
       -DWITH_LIBXML2=ON \
       -DWITH_THREAD_SAFETY=ON \
       -DWITH_GIF=ON \
       -DWITH_EXEMPI=ON \
       -DWITH_HARFBUZZ=ON \
       -DCMAKE_PREFIX_PATH="/usr/proj92;/usr/geos312;/usr/gdal36;/usr/pgsql-16" ..

make -j4
popd

%install
pushd build
rm -rf $RPM_BUILD_ROOT
make install DESTDIR=$RPM_BUILD_ROOT
popd

mkdir -p %{buildroot}%{_libexecdir}
mkdir -p %{buildroot}/%{_sysconfdir}/php.d
mkdir -p %{buildroot}%{_libdir}/php/modules
mkdir -p %{buildroot}%{_datadir}/%{name}

mv %{buildroot}/%{_bindir}/mapserv %{buildroot}%{_libexecdir}/mapserv%{MS_REL}

install -p -m 644 xmlmapfile/mapfile.xsd %{buildroot}%{_datadir}/%{name}
install -p -m 644 xmlmapfile/mapfile.xsl %{buildroot}%{_datadir}/%{name}

# install php config file
mkdir -p %{buildroot}%{_sysconfdir}/php.d/
cat > %{buildroot}%{_sysconfdir}/php.d/%{name}.ini <<EOF
; Enable %{name} extension module
extension=php_mapscript%{MS_REL}.so
EOF

# cleanup junks
for junk in {*.pod,*.bs,.packlist} ; do
find %{buildroot} -name "$junk" -exec rm -rf '{}' \;
done

%files
%defattr(-,root,root)
%doc HISTORY.TXT  
%doc INSTALL MIGRATION_GUIDE.txt
%doc symbols tests
%doc fonts
%{_bindir}/legend%{MS_REL}
%{_bindir}/msencrypt%{MS_REL}
%{_bindir}/scalebar%{MS_REL}
%{_bindir}/shp2img%{MS_REL}
%{_bindir}/shptree%{MS_REL}
%{_bindir}/shptreetst%{MS_REL}
%{_bindir}/shptreevis%{MS_REL}
%{_bindir}/sortshp%{MS_REL}
%{_bindir}/tile4ms%{MS_REL}
%{_libdir}/libmapserver.so
%{_libdir}/libmapserver.so.2
%{_libdir}/libmapserver.so.%{version}
%{_libexecdir}/mapserv
%dir %{_datadir}/%{name}
%{_datadir}/%{name}/*
/usr/include/mapserver/

%files -n php-%{name}
%defattr(-,root,root)
%doc mapscript/php/README
%doc mapscript/php/examples
%config(noreplace) %{_sysconfdir}/php.d/%{name}.ini
%{_libdir}/php/modules/php_mapscript%{MS_REL}.so

