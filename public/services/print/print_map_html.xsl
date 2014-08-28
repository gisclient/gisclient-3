<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="map">
        <html>
            <head>
                <link href="{gisclient-folder}css/print_map.css" rel="stylesheet" type="text/css" />
            </head>
            <body>
				<div class="print_map" style="width:{map-width}cm">
					<div class="logo">
						<div class="dx"><img src="{map-logo-dx}" /></div>
						<div class="sx"><img src="{map-logo-sx}" /></div>
					</div>
					<div class="map">
						<xsl:if test="north-arrow!=''">
						<div id="north_arrow" class="north_arrow">
							<img src="{north-arrow}" />
						</div>
						</xsl:if>
						<img src="{map-img}" style="width:100%" />
					</div>
					<div class="info">
						<div class="sx">
                            <xsl:if test="map-date!=''">
                                <xsl:choose>
                                    <xsl:when test="//map-lang='de'">Maßstab 1:</xsl:when>
                                    <xsl:otherwise>Scala 1:</xsl:otherwise>
                                </xsl:choose>
                                <xsl:value-of select="map-scale" />
                            </xsl:if>
						</div>
						<div class="dx">
                            <xsl:if test="map-date!=''">
                                <xsl:choose>
                                    <xsl:when test="//map-lang='de'">Datum: </xsl:when>
                                    <xsl:otherwise>Data: </xsl:otherwise>
                                </xsl:choose>
                                <xsl:value-of select="map-date" />
                            </xsl:if>
						</div>
						<div class="clearer" />
					</div>
                    <xsl:if test="map-text!=''">
					<div class="maptext"><xsl:value-of select="map-text" /></div>
                    </xsl:if>
				<xsl:apply-templates select="map-legend" />
				</div>
            </body>
        </html>
    </xsl:template>

    <xsl:template match="map-legend">
				<div class="legend">
 					<!--<div class="title">
						<xsl:choose>
							<xsl:when test="//map-lang='de'">Legende:</xsl:when>
							<xsl:otherwise>Legenda:</xsl:otherwise>
						</xsl:choose>
					</div> -->
					<xsl:apply-templates select="legend-group" />
				</div>
    </xsl:template>

    <xsl:template match="legend-group">
					<div class="group">
						<div class="title">
							<xsl:if test="group-icon!=''"><img src="{group-icon}" /></xsl:if>
							<xsl:value-of select="group-title" />
						</div>
						<xsl:apply-templates select="group-block" />
						<div class="clearer" />
					</div>
    </xsl:template>

    <xsl:template match="group-block">
							<xsl:apply-templates select="group-item" />
    </xsl:template>

    <xsl:template match="group-item">
						<div class="item">
							<xsl:if test="icon!=''">
								<img src="{icon}" />
								<xsl:value-of select="title" />
							</xsl:if>
						</div>
    </xsl:template>

</xsl:stylesheet>