<?php

function page_header($title)
{
  echo "<HTML>\n<HEAD>\n<TITLE>";
  echo $title;
  echo "</TITLE>\n</HEAD>\n<BODY>\n";
  echo "<H1>";
  echo $title;
  echo "</H1>\n";
}

function page_footer()
{
  echo "</BODY>\n</HTML>\n";
}


?> 