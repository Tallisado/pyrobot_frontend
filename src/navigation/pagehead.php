<?
/*
 * SIPp Webfrontend	- Web tool to create, manage and run SIPp test cases
 * Copyright (c) 2008 Mario Smeritschnig
 * Idea, support, planning, guidance Michael Hirschbichler
 *
 * * * BEGIN LICENCE * * *
 *
 * This file is part of SIPp Webfrontend.
 * 
 * SIPp Webfrontend is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * SIPp Webfrontend is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with SIPp Webfrontend.  If not, see <http://www.gnu.org/licenses/>.
 *
 * * * END LICENCE * * *
 *
 */

// If you want to add a new tab to the navigation, do it here. Syntax: addTab($title, $width, $url)
require_once "register.php";

if($admin) addTab("Manage RF Scenarios", 150, "testcode.php");
addTab("Manage Tests", 150, "testsuites.php");
addTab("System Information", 150, "info.php");

showTabs(); 

?>
<div style="width:100%; height:100px"></div>
<label style="position:absolute; right:0px; top:0px;" >Pyrobot WEb FRONtENd</label>