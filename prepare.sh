cp master-setlist.txt setlist.txt

if [ -f indexing-report.txt ]; then
  rm indexing-report.txt
fi

if [ -f launch-report.txt ]; then
  rm launch-report.txt
fi

php deleting.php

