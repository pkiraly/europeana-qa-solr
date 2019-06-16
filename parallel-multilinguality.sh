#!/usr/bin/env bash

DIR=/projects/pkiraly/data-export/v2019-03/parts-multilingual-saturation
LOG=index-multilingual-saturation.log

if [[ -e $LOG ]]; then
  rm $LOG;
fi

task() {
  php incremental-index-multilingual-saturation.php --port 8984 --collection qa-2019-03 --with-check --file "$DIR/$1";
}


FILES="part01.csv part02.csv part03.csv part04.csv part05.csv part06.csv part07.csv part08.csv part09.csv part10.csv part11.csv part12.csv part13.csv part14.csv part15.csv part16.csv part17.csv part18.csv part19.csv part20.csv part21.csv part22.csv part23.csv part24.csv part25.csv part26.csv part27.csv part28.csv part29.csv part30.csv part31.csv part32.csv part33.csv part34.csv part35.csv part36.csv part37.csv part38.csv part39.csv part40.csv part41.csv part42.csv part43.csv part44.csv part45.csv part46.csv part47.csv part48.csv part49.csv part50.csv part51.csv part52.csv part53.csv part54.csv part55.csv part56.csv part57.csv part58.csv"

N=3
(
  for FILE in $FILES; do 
    ((i=i%N)); ((i++==0)) && wait
    d=`date +%T.%N`;
    echo "$d $FILE" >> $LOG
    task "$FILE" &
  done
)
