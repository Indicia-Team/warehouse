<?xml version="1.0" encoding="ISO-8859-1"?>

<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/">
<html>
	<head>
		<title>Xml output generated from Indicia data services</title>
	</head>
	<body>
	    <xsl:apply-templates/>
	</body>
</html>
</xsl:template>

<xsl:template match="/*">
<table>
	<thead>
		<tr><xsl:for-each select="*[position() = 1]/*">
			<th><xsl:value-of select="local-name()"/></th>
		</xsl:for-each></tr>
	</thead>
	<xsl:apply-templates/>
</table>
</xsl:template>

<xsl:template match="/*/*">
<tr>
    <xsl:apply-templates/>
</tr>
</xsl:template>

<xsl:template match="/*/*/*">
<td>
    <xsl:value-of select="."/>
</td>
</xsl:template>

</xsl:stylesheet>