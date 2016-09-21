<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:fo="http://www.w3.org/1999/XSL/Format">
    <xsl:template match="Report">
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
					
                    <fo:block font-family="sans-serif" font-size="10pt">
                        <fo:table table-layout="fixed" width="100%">
                            <fo:table-column column-width="proportional-column-width(1)"/>
                            <fo:table-column column-width="{total-width}cm"/>
                            <fo:table-column column-width="proportional-column-width(1)"/>
                            <fo:table-body>
                              <fo:table-row>
                                  <fo:table-cell column-number="2">
                                    
                                    <!-- logos -->
                                    <fo:table border="1pt solid #000000">
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

                                   <!-- table content -->
                                   <xsl:apply-templates/>
                                   
                                 </fo:table-cell>
                            </fo:table-row>
                          </fo:table-body>
                        </fo:table>                   
                    </fo:block>
                </fo:flow>
            </fo:page-sequence>
        </fo:root>
    </xsl:template>

    <xsl:template match="ReportData">
        <fo:table>
            <fo:table-header>
                <fo:table-row>
                    <xsl:apply-templates select="ColumnHeaders/ColumnHeader"/>                  
                </fo:table-row>
            </fo:table-header>
            <fo:table-body>
                <xsl:apply-templates select="Rows/Row"/>
            </fo:table-body>
        </fo:table>
    </xsl:template>

    <xsl:template match="ColumnHeader">
        <fo:table-cell width="{Width}cm" border="solid black 1px" padding="2px" font-weight="bold" text-align="center">
            <fo:block><xsl:value-of select="Name"/></fo:block>
        </fo:table-cell>
    </xsl:template>

    <xsl:template match="Row">
        <fo:table-row>
            <xsl:apply-templates/>
        </fo:table-row>
    </xsl:template>

    <xsl:template match="Column">
        <fo:table-cell border="solid black 1px" padding="2px">
            <fo:block><xsl:value-of select="."/></fo:block>
        </fo:table-cell>
    </xsl:template>     
    

</xsl:stylesheet>