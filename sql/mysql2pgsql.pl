#!/usr/bin/perl
# Usage: mysql2pgsql.pl < flyspray.mysql > flyspray.pgsql
# 
# WARNING: this migration script was tested with flyspray-0.9.7.mysql ONLY!
# It IS NOT meant to be a general mysql2pgsql converter (but may be in the
# future ;)
# 
use strict;
my $file;

while (<>) {
    if (($_ !~ m/^\s*$/) && ($_ !~ m/;\s*$/)) {
	chomp;
    }
    $file .= $_;
}

my @lines = split /\n/, $file;

foreach my $line (@lines) {
    $line =~ s/`//g;

    if ($line =~ m/create\s+table /i) {
	$line = createTable($line);
    }

    print "$line\n";
}


sub createTable {
    my $def = shift;

    if ($def =~ m/(create\s+table\s+)(\w+)\s+\((.*)\)\s*type=\w+\s+(comment=.*\s+)?auto_increment=(\d+)/i) {
	my ($pre, $table_name, $cols, $dummy, $inc) = ($1, $2, $3, $4, $5);
	my $autoincrement_column;
	my @cols = split /\s*,\s*/, $cols;
	# spaces before and after
	@cols = map {s/(^\s+|\s+$)//g;$_} @cols;
	# .*int() => numeric
	@cols = map {s/\s\w+int/ NUMERIC/i if /\s\w+int/i; $_} @cols;
	# varchar => text
	@cols = map {s/\svarchar\(.*?\)/ TEXT/i if /\svarchar/i; $_} @cols;
	# longtext => text
	@cols = map {s/\slongtext/ TEXT/i if /\slongtext/i; $_} @cols;
	
	@cols = map {
	    if (/(\w+)\s+([\w\d\(\)]+)(.*)\s+auto_increment/) {
		my ($col, $type, $rest) = ($1, $2, $3);
		$autoincrement_column = $col;
		s/(\w+)\s+([\w\d()]+)(.*)\s+auto_increment/$1 INT8 $3 DEFAULT nextval('"${table_name}_${autoincrement_column}_seq"'::text)/;
	    }
	    $_;
       	} @cols;
	
	$def = "$pre $table_name (\n\t".join(",\n\t", @cols)."\n);";
	if (defined $autoincrement_column) {
	    $def = "CREATE SEQUENCE \"${table_name}_${autoincrement_column}_seq\" START WITH $inc;\n$def";
	}
    }
    
    return $def;
}
