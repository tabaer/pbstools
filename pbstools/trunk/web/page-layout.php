<?php

function page_header($title)
{
  echo "<HTML>\n<HEAD>\n";
  echo "<TITLE>".$title."</TITLE>\n";
  echo "<LINK rel=stylesheet type=\"text/css\" href=\"default.css\">\n</HEAD>\n<BODY>\n";
  echo "<H1>";
  echo $title;
  echo "</H1>\n";
}

function page_footer()
{
  echo "</BODY>\n</HTML>\n";
}


?> 