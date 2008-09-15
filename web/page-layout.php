<?php
# Copyright 2006, 2007 Ohio Supercomputer Center
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
  echo "  <TD width=\"15%\" valign=\"top\" bgcolor=\"#dedfdf\">\n";
  echo "    <UL><U>Job info by</U>\n";
  echo "      <LI><A href=\"jobinfo.php\">Job id</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-user.php\">User</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-group.php\">Group</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-account.php\">Account</A></LI>\n";
  echo "      <LI><A href=\"jobs-by-node.php\">Node</A></LI>\n";
  echo "    </UL>\n";
  echo "    <UL><U>Job stats by</U>\n";
  echo "      <LI><A href=\"jobstats-by-nproc.php\">CPU Count</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-queue.php\">Job Class</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-walltime.php\">Job Length</A></LI>\n";
# NOTE By-institution jobstats involves OSC site-specific logic.  You may
# want to comment it out.
  echo "      <LI><A href=\"jobstats-by-institution.php\">Institution</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-account.php\">Account</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-group.php\">Group</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-user.php\">User</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-quarter.php\">Quarter</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-month.php\">Month</A></LI>\n";
  echo "      <LI><A href=\"jobstats-by-week.php\">Week</A></LI>\n";
  echo "      <LI><A href=\"jobstats.php\">All</A></LI>\n";
  echo "    </UL>\n";
  echo "    <UL><U>Software usage by</U>\n";
  echo "      <LI><A href=\"software-usage.php\">System</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-queue.php\">Job Class</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-walltime.php\">Job Length</A></LI>\n";
# NOTE By-institution jobstats involves OSC site-specific logic.  You may
# want to comment it out.
  echo "      <LI><A href=\"software-usage-by-institution.php\">Institution</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-account.php\">Account</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-group.php\">Group</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-user.php\">User</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-quarter.php\">Quarter</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-month.php\">Month</A></LI>\n";
  echo "      <LI><A href=\"software-usage-by-week.php\">Week</A></LI>\n";
  echo "    </UL>\n";
  echo "    <UL><U>Miscellaneous reports</U>\n";
  echo "      <LI><A href=\"usage-summary.php\">Usage Summary</A></LI>\n";
  echo "      <LI><A href=\"problem-jobs.php\">Problematic Jobs</A></LI>\n";
  echo "      <LI><A href=\"active-users.php\">Most Active Users</A></LI>\n";
  echo "      <LI><A href=\"active-groups.php\">Most Active Groups</A></LI>\n";
  echo "      <LI><A href=\"active-accounts.php\">Most Active Accounts</A></LI>\n";
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

function checkbox($label,$name)
{
  echo "<INPUT type=\"checkbox\" name=\"".$name."\" value=\"1\"> ".$label."<BR>\n";
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

function pulldown($name,$label,$choices,$default)
{
  echo $label.": <SELECT name=\"".$name."\" size=\"1\">\n";
  foreach ($choices as $choice)
    {
      if ( $choice==$default )
	{
	  echo "<OPTION selected=\"selected\">".$choice."\n";
	}
      else
	{
	  echo "<OPTION>".$choice."\n";
	}
    }
  echo "</SELECT><BR>\n";  
}

function textfield($name,$label,$default,$width)
{
  echo $label.": <INPUT type=\"text\" name=\"".$name."\" size=\"".$width."\" value=\"".$default."\"><BR>\n";
}

function bookmarkable_url()
{
  # the following code is derived from an example at
  # http://www.webcheatsheet.com/PHP/get_current_page_url.php
  $pageURL = 'http';
  if ($_SERVER["HTTPS"] == "on")
    {
      $pageURL .= "s";
    }
  $pageURL .= "://";
  if ( $_SERVER["SERVER_PORT"]!=80 &&
       ($_SERVER["HTTPS"]=="on" &&  $_SERVER["SERVER_PORT"]!=443) )
    {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
    }
  else
    {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }
  if ( !preg_match('/\?/',$_SERVER["REQUEST_URI"]) )
    {
      $first = 1;
      foreach (array_keys($_POST) as $param)
	{
	  if ( $first==1 )
	    {
	      $pageURL .= "?".$param;
	      $first = 0;
	    }
	  else
	    {
	      $pageURL .= "&".$param;
	    }
	  if ( isset($_POST[$param]) && $_POST[$param]!="" )
	    {
	      $pageURL .= "=".$_POST[$param];
	    }
	}
    }
  echo "<P>Bookmarkable URL for this report:  <A href=\"".$pageURL."\"><PRE>".htmlspecialchars($pageURL)."</PRE></A></P>\n";
}

?>
