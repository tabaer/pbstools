<?php
# Copyright 2006 Ohio Supercomputer Center
# Revision info:
# $HeadURL$
# $Revision$
# $Date$

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
  echo "    <UL><U>Other reports</U>\n";
  echo "      <LI><A href=\"software-usage.php\">Software usage</A></LI>";
  echo "    </UL>\n";
  echo "  </TD>\n";
  echo "  <TD width=\"85%\" valign=\"top\" bgcolor=\"ghostwhite\">\n";
}

function page_footer()
{
  echo "  </TD>\n</TR>\n</TABLE>\n</BODY>\n</HTML>\n";
}

function checkboxes_from_array($array)
{
  foreach ($array as $value)
    {
      echo "<INPUT type=\"checkbox\" name=\"".$value."\" value=\"1\"> ".$value."<BR>\n";
    }
}

?> 