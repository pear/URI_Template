<?php
/**
 * Copyright (c) 2007-2008 Martin Jansen
 * 
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once "PEAR/PackageFileManager2.php";

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$p2 = new PEAR_PackageFileManager2();

$p2->setOptions(array(
    "packagedirectory"  => ".",
    "baseinstalldir"    => "URI",
    "simpleoutput" => true,
    "ignore" => array("package.xml", "makepackage.php", "runtests.sh", "URI_Template/CVS/", "URI_Template/*/CVS/", "*.svn/", "*.tgz")));

$p2->setPackageType("php");
$p2->generateContents();

$p2->setPackage("URI_Template");
$p2->setChannel("pear.php.net");
$p2->setSummary("Parser for URI Templates.");
$p2->setDescription("URI_Template is a parser for URI Templates as defined in the URI Template specification (http://bitworking.org/projects/URI-Templates).");
$p2->setReleaseVersion("0.3.0");
$p2->setReleaseStability("alpha");
$p2->setAPIVersion("0.3.0");
$p2->setAPIStability("alpha");
$p2->setNotes("
* Implemented Feature Request #14763: Added new method getTemplateVariables() which returns an array containing the template variables names. (Provided by David Jean Louis.)
* Changed visiblity of internal methods from private to protected. (Based on a patch by David Jean Louis.)
");
$p2->setLicense("BSD License", "http://www.opensource.org/licenses/bsd-license.php");

$p2->setPhpDep("5.2.0");
$p2->setPearinstallerDep("1.6.2");

$p2->addMaintainer("lead", "mj", "Martin Jansen", "mj@php.net");

$p2->addReplacement("Templates.php", "package-info",
    "@package_version@", "version");

$p2->writePackageFile();
