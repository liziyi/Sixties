<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:data="jabber:x:data">
    <xsl:output method="html" indent="no" encoding="UTF-8" omit-xml-declaration="yes"/>
    <xsl:template match="data:x[@type='form']">
        <form class="form_form">
            <xsl:apply-templates select="data:title"/>
            <xsl:apply-templates select="data:instructions"/>
            <xsl:apply-templates select="data:field"/>
            <xsl:apply-templates select="data:reported"/>
            <xsl:apply-templates select="data:item"/>
        </form>
    </xsl:template>
    <xsl:template match="data:instructions">
        <div class="form_instructions">
            <xsl:value-of select="."/>
        </div>
    </xsl:template>
    <xsl:template match="data:title">
        <div class="form_title">
            <xsl:value-of select="."/>
        </div>
    </xsl:template>
    <xsl:template match="data:field">
        <xsl:variable name="field_name">
            <xsl:value-of select="@var"/>
        </xsl:variable>
        <xsl:element name="div">
            <xsl:attribute name="class">form_field<xsl:if test="./data:required"> form_field_required</xsl:if> form_field_type_<xsl:value-of select="@type"/></xsl:attribute>
            <xsl:if test="@label!=''">
                <label class="form_field_label">
                    <xsl:value-of select="@label"/>
                </label>
            </xsl:if>
            <xsl:choose>
                <!-- -=-=-=-=-= BOOLEAN =-=-=-=-=- -->
                <xsl:when test="@type='boolean'">
                    <xsl:variable name="field_value">
                        <xsl:value-of select="./data:value/text()"/>
                    </xsl:variable>
                    <xsl:element name="input">
                        <xsl:attribute name="type">radio</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value">true</xsl:attribute>
                        <xsl:if test="$field_value='1' or $field_value='true'">
                            <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                    </xsl:element>
                    <label>true</label>
                    <xsl:element name="input">
                        <xsl:attribute name="type">radio</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value">false</xsl:attribute>
                        <xsl:if test="$field_value='0' or $field_value='false'">
                            <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                    </xsl:element>
                    <label>false</label>
                    <xsl:element name="input">
                        <xsl:attribute name="type">radio</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value"></xsl:attribute>
                    </xsl:element>
                    <label>unset</label>
                </xsl:when>
                <!-- -=-=-=-=-= FIXED =-=-=-=-=- -->
                <xsl:when test="@type='fixed'">
                    <span class="form_field_fixed">
                        <xsl:for-each select="data:value">
                            <xsl:value-of select='.'/>
                            <xsl:text>&#10;</xsl:text>
                        </xsl:for-each>
                    </span>
                </xsl:when>
                <!-- -=-=-=-=-= HIDDEN =-=-=-=-=- -->
                <xsl:when test="@type='hidden'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">hidden</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value">
                            <xsl:value-of select='data:value'/>
                        </xsl:attribute>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= JID-MULTI =-=-=-=-=- -->
                <xsl:when test="@type='jid-multi'">
                    <xsl:element name="textarea">
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="cols">40</xsl:attribute>
                        <xsl:attribute name="rows">10</xsl:attribute>
                        <xsl:for-each select="data:value">
                            <xsl:value-of select='.'/>
                            <xsl:text>&#10;</xsl:text>
                        </xsl:for-each>
                        <xsl:text>&#10;</xsl:text>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= JID-SINGLE =-=-=-=-=- -->
                <xsl:when test="@type='jid-single'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">text</xsl:attribute>
                        <xsl:attribute name="size">40</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value">
                            <xsl:value-of select='data:value'/>
                        </xsl:attribute>

                        <xsl:attribute name="selected">selected</xsl:attribute>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= LIST-MULTI =-=-=-=-=- -->
                <xsl:when test="@type='list-multi'">
                    <xsl:element name="select">
                        <xsl:attribute name="multiple">multiple</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:for-each select="data:option">
                            <xsl:variable name="field_option_value">
                                <xsl:for-each select="data:value">
                                    <xsl:value-of select='.'/>
                                </xsl:for-each>
                            </xsl:variable>
                            <xsl:element name="option">
                                <xsl:attribute name="value">
                                    <xsl:value-of select='$field_option_value'/>
                                </xsl:attribute>
                                <xsl:if test="count(../data:value[text()=$field_option_value])=1">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:choose>
                                    <xsl:when test="@label!=''">
                                        <xsl:value-of select='@label'/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select='$field_option_value'/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:element>
                        </xsl:for-each>
                        <xsl:if test="count(data:option)=0">
                            <xsl:text>&#10;</xsl:text>
                        </xsl:if>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= LIST-SINGLE =-=-=-=-=- -->
                <xsl:when test="@type='list-single'">
                    <xsl:variable name="field_value">
                        <xsl:for-each select="data:value">
                            <xsl:value-of select='.'/>
                        </xsl:for-each>
                    </xsl:variable>
                    <xsl:element name="select">
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:if test="count(data:value)!=1">
                            <option value="" selected="selected"></option>
                        </xsl:if>
                        <xsl:for-each select="data:option">
                            <xsl:variable name="field_option_value">
                                <xsl:for-each select="data:value">
                                    <xsl:value-of select='.'/>
                                </xsl:for-each>
                            </xsl:variable>
                            <xsl:element name="option">
                                <xsl:attribute name="value">
                                    <xsl:value-of select='$field_option_value'/>
                                </xsl:attribute>
                                <xsl:if test="$field_option_value=$field_value">
                                    <xsl:attribute name="selected">selected</xsl:attribute>
                                </xsl:if>
                                <xsl:choose>
                                    <xsl:when test="@label!=''">
                                        <xsl:value-of select='@label'/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select='$field_option_value'/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:element>
                        </xsl:for-each>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= TEXT-MULTI =-=-=-=-=- -->
                <xsl:when test="@type='text-multi'">
                    <xsl:element name="textarea">
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="cols">40</xsl:attribute>
                        <xsl:attribute name="rows">10</xsl:attribute>
                        <xsl:for-each select="data:value">
                            <xsl:value-of select='.'/>
                            <xsl:text>&#10;</xsl:text>
                        </xsl:for-each>
                        <xsl:if test="count(data:value)=0">
                            <xsl:text>&#10;</xsl:text>
                        </xsl:if>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= TEXT-PRIVATE =-=-=-=-=- -->
                <xsl:when test="@type='text-private'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">password</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value">
                            <xsl:value-of select="data:value"/>
                        </xsl:attribute>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= TEXT-SINGLE =-=-=-=-=- -->
                <xsl:when test="@type='text-single'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">text</xsl:attribute>
                        <xsl:attribute name="size">40</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="value">
                            <xsl:value-of select="data:value"/>
                        </xsl:attribute>
                    </xsl:element>
                </xsl:when>
                <xsl:otherwise>
                </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="./data:required">
                *
            </xsl:if>
            <xsl:if test="./data:desc">
                <span class="form_field_legend">
                    <xsl:value-of select="./data:desc"/>
                </span>
            </xsl:if>
        </xsl:element>
    </xsl:template>
    <xsl:template match="data:reported">
        <div class="form_error">UNIMPLEMENTED : reported</div>
    </xsl:template>
    <xsl:template match="data:item">
        <div class="form_error">UNIMPLEMENTED : item</div>
    </xsl:template>
</xsl:stylesheet>
