<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">
    <xsl:template match="map">
        <fo:root>
            <fo:layout-master-set>
                <!-- A4 Portrait -->
                <fo:simple-page-master master-name="A4P" page-height="29.7cm" page-width="21cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A4 Landscape -->
                <fo:simple-page-master master-name="A4L" page-height="21cm" page-width="29.7cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A3 Portrait -->
                <fo:simple-page-master master-name="A3P" page-height="42cm" page-width="29.7cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A3 Landscape -->
                <fo:simple-page-master master-name="A3L" page-height="29.7cm" page-width="42cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A2 Portrait -->
                <fo:simple-page-master master-name="A2P" page-height="59.4cm" page-width="42cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A2 Landscape -->
                <fo:simple-page-master master-name="A2L" page-height="42cm" page-width="59.4cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A1 Portrait -->
                <fo:simple-page-master master-name="A1P" page-height="84.1cm" page-width="59.4cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A1 Landscape -->
                <fo:simple-page-master master-name="A1L" page-height="59.4cm" page-width="84.1cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A0 Portrait -->
                <fo:simple-page-master master-name="A0P" page-height="118.9cm" page-width="84.1cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A0 Landscape -->
                <fo:simple-page-master master-name="A0L" page-height="84.1cm" page-width="118.9cm" margin-top="1.905cm" margin-bottom="1.905cm" margin-left="1.905cm" margin-right="1.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
            </fo:layout-master-set>

            <!-- Template 1 -->
            <fo:page-sequence master-reference="{page-layout}">

                <!-- Body content -->
                <fo:flow flow-name="xsl-region-body">
					
					<!-- page -->
					<fo:block font-family="sans-serif" font-size="10pt" border="1pt solid #000000">

						<!-- logos -->
						<fo:table border-bottom="1pt solid #000000">
							<fo:table-body>
								<fo:table-row>
									<fo:table-cell>
										<fo:block><fo:external-graphic src="{map-logo-sx}" content-height="1cm" /></fo:block>
									</fo:table-cell>
									<fo:table-cell text-align="right">
										<fo:block><fo:external-graphic src="{map-logo-dx}" content-height="1cm" /></fo:block>
									</fo:table-cell>
								</fo:table-row>
							</fo:table-body>
						</fo:table>

						<!-- map -->
						<fo:block border-bottom="1pt solid #000000" text-align="center"><fo:external-graphic src="{map-img}" content-width="{map-width}cm" /></fo:block>

						<!-- scale -->
						<fo:table>
							<fo:table-body>
								<fo:table-row>
									<fo:table-cell padding="2px">
										<fo:block>
											<xsl:choose>
												<xsl:when test="//map-lang='de'">Maßstab 1:</xsl:when>
												<xsl:otherwise>Scala 1:</xsl:otherwise>
											</xsl:choose>
											<xsl:value-of select="map-scale" />
										</fo:block>
									</fo:table-cell>
									<fo:table-cell padding="2px">
										<fo:block>
											<xsl:if test="map-date!=''">
												<xsl:choose>
													<xsl:when test="//map-lang='de'">Datum: </xsl:when>
													<xsl:otherwise>Data: </xsl:otherwise>
												</xsl:choose>
												<xsl:value-of select="map-date" />
											</xsl:if>
										</fo:block>
									</fo:table-cell>
								</fo:table-row>
								<xsl:if test="map-text!=''">
									<fo:table-row border-top="1pt solid #000000">
										<fo:table-cell padding="2px" number-columns-spanned="2">
											<fo:block white-space="pre"><xsl:value-of select="map-text" /></fo:block>
										</fo:table-cell>
									</fo:table-row>
								</xsl:if>
							</fo:table-body>
						</fo:table>					
					</fo:block>
					<!-- north arrow -->
					<xsl:if test="north-arrow!=''">
					<fo:block-container absolute-position="absolute" right="0.5cm" top="1.5cm" text-align="right">
						<fo:block><fo:external-graphic src="{north-arrow}" content-height="2cm" /></fo:block>
					</fo:block-container>
					</xsl:if>

					<fo:block break-after="page" />
					<xsl:if test="map-legend!=''">
					<fo:table font-family="sans-serif" font-size="10pt" border="1pt solid #000000">
						<fo:table-column column-width="33%" />
						<fo:table-column column-width="33%" />
						<fo:table-column column-width="34%" />
						<fo:table-body>
							<xsl:apply-templates select="map-legend" />
						</fo:table-body>
					</fo:table>
					</xsl:if>


                </fo:flow>
            </fo:page-sequence>
        </fo:root>
    </xsl:template>

    <xsl:template match="map-legend">
        <!-- <fo:table-row>
            <fo:table-cell padding="2px" number-columns-spanned="3" border-top="1pt solid #000000">
                <fo:block font-weight="bold">
                    <xsl:choose>
                        <xsl:when test="//map-lang='de'">Legende:</xsl:when>
                        <xsl:otherwise>Legenda:</xsl:otherwise>
                    </xsl:choose>
                </fo:block>
            </fo:table-cell>
        </fo:table-row> -->
        <xsl:apply-templates select="legend-group" />
    </xsl:template>

    <xsl:template match="legend-group">
        <fo:table-row>
            <fo:table-cell padding="0px" number-columns-spanned="3" border-top="1pt dotted #000000">
                <fo:block></fo:block>
            </fo:table-cell>
        </fo:table-row>
        <fo:table-row keep-with-next="always">
            <fo:table-cell number-columns-spanned="3" padding="2px">
                <fo:block text-decoration="underline">
                    <xsl:if test="group-icon!=''"><fo:external-graphic src="{group-icon}" />&#160;</xsl:if>
                    <xsl:value-of select="group-title" />
                </fo:block>
            </fo:table-cell>
        </fo:table-row>
        <xsl:apply-templates select="group-block" />
    </xsl:template>

    <xsl:template match="group-block">
        <fo:table-row>
            <xsl:apply-templates select="group-item" />
        </fo:table-row>
    </xsl:template>

    <xsl:template match="group-item">
        <fo:table-cell padding="2px">
            <fo:block><fo:external-graphic src="{icon}" />&#160;<xsl:value-of select="title" /></fo:block>
        </fo:table-cell>
    </xsl:template>

</xsl:stylesheet>