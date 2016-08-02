<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$
require_once 'dbutils.php';

function page_header($title)
{
  echo "<HTML>\n<HEAD>\n";
  echo "<TITLE>".$title."</TITLE>\n";
  echo "<LINK rel=stylesheet type=\"text/css\" href=\"default.css\">\n</HEAD>\n<BODY>\n";
  echo "<TABLE height=\"100%\" width=\"100%\" bgcolor=\"gray\">\n";
  echo "<TR height=\"10%\">\n";
  echo "  <TD width=\"15%\" bgcolor=\"#dedfdf\">\n";
  echo "  </TD>\n";
  echo "  <TD bgcolor=\"#dedfdf\">\n";
  echo "    <H1>".$title."</H1>\n";
  echo "  </TD>\n";
  echo "</TR>\n";
  echo "<TR height=\"90%\">\n";
  echo "  <TD width=\"15%\" valign=\"top\" bgcolor=\"#dedfdf\">";
  echo "    <UL><U>Job info by</U>\n";
  echo "      <LI><A href=\"jobinfo.php\">Job id</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-user.php\">User</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-group.php\">Group</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-node.php\">Node</A></LI>\n";
  echo "    </UL>\n";
  echo "    <UL><U>Job stats by</U>\n";
  echo "      <LI><A href=\"jobstats-by-nproc.php\">CPU Count</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-queue.php\">Job Class</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-institution.php\">Institution</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-group.php\">Group/Project</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-user.php\">User</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-month.php\">Month</A></LI>\n";
  echo "      <LI><A href=\"jobstats.php\">All</A></LI>\n";
  echo "    </UL>\n";
  echo "    <UL><U>Software usage by</U>\n";
  echo "      <LI><A href=\"software-usage.php\">System</A></LI>";
  echo "      <LI><A href=\"software-usage-by-month.php\">Month</A></LI>";
  echo "    </UL>\n";
  echo "  </TD>\n";
  echo "  <TD width=\"85%\" valign=\"top\" bgcolor=\"ghostwhite\">\n";
}

function page_footer()
{
  echo "  </TD>\n</TR>\n</TABLE>\n</BODY>\n</HTML>\n";
}

function begin_form($target)
{
  echo "<FORM method=\"POST\" action=\"".$target."\">\n";
}

function end_form()
{
  echo "<INPUT type=\"submit\">\n<INPUT type=\"reset\">\n</FORM>\n";
}

function text_field($label,$field,$width)
{
  echo $label.":  <INPUT type=\"text\" name=\"".$field."\" size=\"".$width."\"><BR>\n";
}

function checkboxes_from_array($label,$array)
{
  echo $label.":<BR>\n";
  foreach ($array as $value)
    {
      echo "<INPUT type=\"checkbox\" name=\"".$value."\" value=\"1\"> ".$value."<BR>\n";
    }
}

function system_chooser()
{
  echo "System:  <SELECT name=\"system\" size=\"1\">\n";
  echo "<OPTION value=\"%\">Any\n";
  $db = db_connect();
  $sql = "SELECT DISTINCT(system) FROM Jobs;";
  $result = db_query($db,$sql);
  while ($result->fetchInto($row))
    {
      $rkeys = array_keys($row);
      foreach ($rkeys as $rkey)
	{
	  echo "<OPTION>".$row[$rkey]."\n";
	}
    }
  db_disconnect($db);
  echo "</SELECT><BR>\n";
}

function virtual_system_chooser()
{
  echo "System:  <SELECT name=\"system\" size=\"1\">\n";
  echo "<OPTION value=\"%\">Any\n";
  foreach (sys_list() as $host)
    {
      echo "<OPTION>".$host."\n";
    }
  echo "</SELECT><BR>\n";
}

function date_fields()
{
  echo "Start date: <INPUT type=\"text\" name=\"start_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";
  echo "End date: <INPUT type=\"text\" name=\"end_date\" size=\"10\"> (YYYY-MM-DD)<BR>\n";
}

?> 