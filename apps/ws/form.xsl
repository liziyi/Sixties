<?xml version="1.0" ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:data="jabber:x:data">
    <xsl:output method="html" indent="no" encoding="UTF-8" omit-xml-declaration="yes"/>
    <xsl:param name="baseid" />
    <!-- sometimes the type is wrong :(
    <xsl:template match="data:x[@type='form']">
     -->
    <xsl:template match="data:x">
        <xsl:element name="form">
            <xsl:attribute name="class">form_form</xsl:attribute>
            <xsl:attribute name="id"><xsl:text>form_</xsl:text><xsl:value-of select="$baseid"/></xsl:attribute>
            <xsl:apply-templates select="data:title"/>
            <xsl:apply-templates select="data:instructions"/>
            <xsl:apply-templates select="data:field">
                <xsl:with-param name="baseid" select="$baseid" />
            </xsl:apply-templates>
            <xsl:apply-templates select="data:reported"/>
        </xsl:element>
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
        <xsl:param name="baseid" />
        <xsl:variable name="field_name">
            <xsl:value-of select="@var"/>
        </xsl:variable>
        <xsl:variable name="field_id">
            <xsl:text>form_field_</xsl:text><xsl:value-of select="$baseid" />_<xsl:number level="single" count="data:field" format="001" />
        </xsl:variable>
        <xsl:element name="div">
            <xsl:attribute name="class">form_field<xsl:if test="./data:required"> form_field_required</xsl:if> form_field_type_<xsl:value-of select="@type"/></xsl:attribute>
            <xsl:if test="@label!=''">
                <xsl:element name="label">
                    <xsl:attribute name="class">form_field_label</xsl:attribute>
                    <xsl:attribute name="for"><xsl:value-of select="$field_id"/></xsl:attribute>
                    <xsl:value-of select="@label"/>
                </xsl:element>
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
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/>_1</xsl:attribute>
                        <xsl:attribute name="value">true</xsl:attribute>
                        <xsl:if test="$field_value='1' or $field_value='true'">
                            <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                    </xsl:element>
                    <xsl:element name="label">
                        <xsl:attribute name="for"><xsl:value-of select="$field_id"/>_1</xsl:attribute>
                        <xsl:text>true</xsl:text>
                    </xsl:element>
                    <xsl:element name="input">
                        <xsl:attribute name="type">radio</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/>_0</xsl:attribute>
                        <xsl:attribute name="value">false</xsl:attribute>
                        <xsl:if test="$field_value='0' or $field_value='false'">
                            <xsl:attribute name="checked">checked</xsl:attribute>
                        </xsl:if>
                    </xsl:element>
                    <xsl:element name="label">
                        <xsl:attribute name="for"><xsl:value-of select="$field_id"/>_0</xsl:attribute>
                        <xsl:text>false</xsl:text>
                    </xsl:element>
                    <!-- 
                    <xsl:element name="input">
                        <xsl:attribute name="type">radio</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/>_N</xsl:attribute>
                        <xsl:attribute name="value"></xsl:attribute>
                    </xsl:element>
                    <xsl:element name="label">
                        <xsl:attribute name="for"><xsl:value-of select="$field_id"/>_N</xsl:attribute>
                        <xsl:text>?</xsl:text>
                    </xsl:element>
                     -->
                </xsl:when>
                <!-- -=-=-=-=-= FIXED =-=-=-=-=- -->
                <xsl:when test="@type='fixed'">
                    <span class="form_field_fixed">
                        <xsl:for-each select="data:value">
                            <xsl:value-of select='.'/>
                            <xsl:text></xsl:text>
                        </xsl:for-each>
                    </span>
                </xsl:when>
                <!-- -=-=-=-=-= HIDDEN =-=-=-=-=- -->
                <xsl:when test="@type='hidden'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">hidden</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
                        <xsl:attribute name="value">
                            <xsl:value-of select='data:value'/>
                        </xsl:attribute>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= JID-MULTI =-=-=-=-=- -->
                <xsl:when test="@type='jid-multi'">
                    <xsl:element name="textarea">
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
                        <xsl:attribute name="cols">40</xsl:attribute>
                        <xsl:attribute name="rows">10</xsl:attribute>
                        <xsl:for-each select="data:value">
                            <xsl:if test="position()>1"><xsl:text>&#10;</xsl:text></xsl:if>
                            <xsl:value-of select='.'/>
                        </xsl:for-each>
                        <xsl:text></xsl:text>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= JID-SINGLE =-=-=-=-=- -->
                <xsl:when test="@type='jid-single'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">text</xsl:attribute>
                        <xsl:attribute name="size">40</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
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
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
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
                            <xsl:text></xsl:text>
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
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
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
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
                        <xsl:attribute name="cols">40</xsl:attribute>
                        <xsl:attribute name="rows">10</xsl:attribute>
                        <xsl:for-each select="data:value">
                            <xsl:if test="position()>1"><xsl:text>&#10;</xsl:text></xsl:if>
                            <xsl:value-of select='.'/>
                        </xsl:for-each>
                        <xsl:if test="count(data:value)=0">
                            <xsl:text></xsl:text>
                        </xsl:if>
                    </xsl:element>
                </xsl:when>
                <!-- -=-=-=-=-= TEXT-PRIVATE =-=-=-=-=- -->
                <xsl:when test="@type='text-private'">
                    <xsl:element name="input">
                        <xsl:attribute name="type">password</xsl:attribute>
                        <xsl:attribute name="name"><xsl:value-of select="$field_name"/></xsl:attribute>
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
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
                        <xsl:attribute name="id"><xsl:value-of select="$field_id"/></xsl:attribute>
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
        <xsl:element name="table">
            <xsl:attribute name="class">form_result</xsl:attribute>
            <xsl:element name="thead">
                <xsl:element name="tr">
                    <xsl:for-each select="data:field">
                        <xsl:element name="th">
                          <xsl:value-of select="@label"></xsl:value-of>
                        </xsl:element>
                    </xsl:for-each>
                </xsl:element>
            </xsl:element>
            <xsl:element name="tbody">
                <xsl:for-each select="following-sibling::data:item">
                    <xsl:element name="tr">
                        <xsl:for-each select="data:field">
                            <xsl:element name="th">
                              <xsl:value-of select="data:value"></xsl:value-of>
                            </xsl:element>
                        </xsl:for-each>
                    </xsl:element>
                </xsl:for-each>
            </xsl:element>
        </xsl:element>
    </xsl:template>
    <xsl:template match="data:item">
    </xsl:template>
</xsl:stylesheet>
