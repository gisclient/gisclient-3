<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">
    <!--
     **** GISClient print templates attributes here!
        gcTemplateName = Ireti per Stralci
    -->
    <xsl:template match="map">
        <fo:root xmlns:fo="http://www.w3.org/1999/XSL/Format"
                 xmlns:pdf="http://xmlgraphics.apache.org/fop/extensions/pdf">
            <fo:layout-master-set>
                <!-- A4 Portrait -->
                <fo:simple-page-master master-name="A4P" page-height="29.7cm" page-width="21cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A4 Landscape -->
                <fo:simple-page-master master-name="A4L" page-height="21cm" page-width="29.7cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A3 Portrait -->
                <fo:simple-page-master master-name="A3P" page-height="42cm" page-width="29.7cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A3 Landscape -->
                <fo:simple-page-master master-name="A3L" page-height="29.7cm" page-width="42cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A2 Portrait -->
                <fo:simple-page-master master-name="A2P" page-height="59.4cm" page-width="42cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A2 Landscape -->
                <fo:simple-page-master master-name="A2L" page-height="42cm" page-width="59.4cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A1 Portrait -->
                <fo:simple-page-master master-name="A1P" page-height="84.1cm" page-width="59.4cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A1 Landscape -->
                <fo:simple-page-master master-name="A1L" page-height="59.4cm" page-width="84.1cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A0 Portrait -->
                <fo:simple-page-master master-name="A0P" page-height="118.9cm" page-width="84.1cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
                    <fo:region-body margin-top="0cm" margin-bottom="0cm" />
                    <fo:region-before extent="0cm" />
                    <fo:region-after extent="0cm" />
                </fo:simple-page-master>
                <!-- A0 Landscape -->
                <fo:simple-page-master master-name="A0L" page-height="84.1cm" page-width="118.9cm" margin-top="0.905cm" margin-bottom="0.905cm" margin-left="0.905cm" margin-right="0.905cm">
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
					<fo:block font-family="sans-serif" font-size="10pt" border="0.2mm solid #000000">

						<!-- map -->
						<fo:block border-bottom="0.2mm solid #000000" border-top="0.2mm solid #000000" font-size="0" text-align="center"><fo:external-graphic src="{map-img}" content-width="{map-width}cm" content-height="{map-height}cm"/></fo:block>

                        <!-- Cartiglio inferiore -->
                        <xsl:choose>
                        <xsl:when test="page-layout='A4L' or page-layout='A4P' or page-layout='A3P'">
    						<fo:table width="100%">
                                <fo:table-column/>
                                <fo:table-column column-width="14%"/>
                                <fo:table-column column-width="14%"/>
                                <fo:table-column column-width="26%"/>
                                <fo:table-column column-width="26%"/>
						        <fo:table-body>
                                    <fo:table-row>
    									<fo:table-cell number-rows-spanned="3">
    									    <fo:block text-align="center" white-space="pre" border-right="solid 0.1mm black">
                                                <fo:external-graphic src="{map-logo-dx}" content-height="1cm" />
                                            </fo:block>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt" >
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Scala:</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Data:</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Coordinate area di stampa min</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Coordinate area di stampa max</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
    								</fo:table-row>
                                    <fo:table-row>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">1: <xsl:value-of select="map-scale" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black"><xsl:value-of select="map-date" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black"><xsl:value-of select="map-minx" /> , <xsl:value-of select="map-miny" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black"><xsl:value-of select="map-maxx" /> , <xsl:value-of select="map-maxy" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
    								</fo:table-row>
                                    <fo:table-row>
                                        <fo:table-cell font-family="arial" font-size="10pt" number-columns-spanned="4">
                                            <fo:block-container height="3.8mm">
    									        <fo:block text-align="center" white-space="pre"><xsl:value-of select="substring(map-text, 1, 130)" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
    								</fo:table-row>
							    </fo:table-body>
						    </fo:table>
                        </xsl:when>
                        <xsl:otherwise>
                            <fo:table width="19.2cm" border-right="solid 0.2mm black">
                                <fo:table-column/>
                                <fo:table-column column-width="14%"/>
                                <fo:table-column column-width="14%"/>
                                <fo:table-column column-width="26%"/>
                                <fo:table-column column-width="26%"/>
						        <fo:table-body>
                                    <fo:table-row>
    									<fo:table-cell number-rows-spanned="3">
    									    <fo:block text-align="center" white-space="pre" border-right="solid 0.1mm black">
                                                <fo:external-graphic src="{map-logo-sx}" content-height="1cm" />
                                            </fo:block>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt" >
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Scala:</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Data:</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Coordinate area di stampa min</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="8pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">Coordinate area di stampa max</fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
    								</fo:table-row>
                                    <fo:table-row>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black">1: <xsl:value-of select="map-scale" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black"><xsl:value-of select="map-date" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black"><xsl:value-of select="map-minx" /> , <xsl:value-of select="map-miny" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
                                        <fo:table-cell font-family="arial" font-size="10pt">
                                            <fo:block-container height="3.9mm">
    									        <fo:block text-align="center" white-space="pre" border-bottom="solid 0.1mm black" border-right="solid 0.1mm black"><xsl:value-of select="map-maxx" /> , <xsl:value-of select="map-maxy" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
    								</fo:table-row>
                                    <fo:table-row>
                                        <fo:table-cell font-family="arial" font-size="10pt" number-columns-spanned="4">
                                            <fo:block-container height="3.8mm">
    									        <fo:block text-align="center" white-space="pre"><xsl:value-of select="substring(map-text, 1, 130)" /></fo:block>
                                            </fo:block-container>
    									</fo:table-cell>
    								</fo:table-row>
							    </fo:table-body>
						    </fo:table>
                        </xsl:otherwise>
                        </xsl:choose>

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
