<?php
//@revised 26 March 2015
//Run this program from the MySQL data directory

/**include the PHP code needed to make the RTF from the text document
*/

include('makeRTFfun.php');  //echoes hello there message too

/* 
SET GLOBAL VARIABLES FOR THE RTF FUNCTIONS
*/

$boldtext="";//for bolded text
$RTFdata=""; //This variable holds the string data for the RTF document to be created
initRTFdoc ();

/*$test4='This is default text (not from a file)';
addRTFpara($test4,2);
$test4=addBold($test4);
addRTFpara($test4,2);
*/

/** Set default database parameters for functions */
$username = "root";  
$password = "";
$hostname = "localhost"; 
$DBname="myprecs";
$MyDocNum=80; //this is the number of the document in myprecs database


/*
CONNECT TO LOCAL MYSQL DATABASE  e.g. MYSQL ON APACHE SERVER USING XAMPP
*/

$dbhandle = mysql_connect($hostname, $username, $password)
  or die("Unable to connect to MySQL"); //this works if this file is on the same server as the DB, accessed via guest?  or login to server then login to DB?
echo "Connected to MySQL<br>";

/* 
SELECT THE DATABASE TO WORK WITH 
*/

$selected = mysql_select_db($DBname,$dbhandle)
  or die("Could not select myprecs");

/**   RETRIEVE THE DOCUMENT NAME FROM THE SQL DATABASE FOR CHOSEN DOC NUMBER, THEN
 SET THE DOCUMENT TITLE READY FOR RTF CONVERSION */
$result = mysql_query('select docname from DocNames where IDdoc = '.$MyDocNum.';');
while ($row = mysql_fetch_array($result)) { 
$TITLE=$row{'docname'};
$DocTitle=$Indent1.'<TT>'.$TITLE.' </TT>}';  //
addstring($DocTitle);
}
/* 
EXECUTE THE DEFINITION CLAUSES SQL QUERY AND RETURN RECORDS 
This performs a query to return the clause ID for all clauses listed as definitions for clauses in
chosen document.
The default sorting order is by name, otherwise insert a field to sort by into the database
*/

$DefHeading="<h1>Definitions</h>"; //definition heading, level 1
addstring($DefHeading); //add a heading before the definitions clauses
echo ($RTFdata);
$result = mysql_query('select * from Clauses 
WHERE Clauses.clauseID IN (select childID from Definitions NATURAL JOIN clauses
JOIN DocRecipe on Clauses.clauseID=DocRecipe.clauseID
where DocRecipe.IDdoc = '.$MyDocNum.'
and Definitions.clauseID =clauses.clauseID) order by name asc;'); 
if($result === FALSE) { 
    die(mysql_error()); // TODO: better error handling
}

/**
FETCH THE DATA FROM THE QUERY RECORDS ROW BY ROW
This will obtain each row of the query results for definitions, returned from mysql_query SELECT statement for separate paragraph
The addstring function just adds the paragraph as text, without any paragraph <tags> for RTF conversion
In the myprecs database, the 'clause' field contains the main text
*/

while ($row = mysql_fetch_array($result)) {   
	$ID=$row{'clauseID'};
	$name=$row{'name'};
	$paragraph=$row{'clause'};  
   echo $paragraph;
   addstring($paragraph); 
}

if($result === FALSE) { 
    die(mysql_error()); // TODO: better error handling
}
/* 
EXECUTE THE MAIN CLAUSES SQL QUERY AND RETURN RECORDS 
*/

$result = mysql_query('select distinct clauseOrder, Clauses.clauseID, name, clause from Clauses 
JOIN DocRecipe on Clauses.clauseID=DocRecipe.clauseID
where DocRecipe.IDdoc = '.$MyDocNum.'
 order by clauseOrder asc;'); //we can have a field to order these by if not by name

if($result === FALSE) { 
    die(mysql_error()); // TODO: better error handling
}


/* 
FETCH THE DATA FROM THE QUERY RECORDS ROW BY ROW
*/
while ($row = mysql_fetch_array($result)) {   //This will obtain each row of the query results returned from mysql_query SELECT statement for separate paragraph
	$ID=$row{'clauseID'};
	$name=$row{'name'};
	$paragraph=$row{'clause'};  //This is the main clause text extracted from MySQL database
   echo $paragraph;
   addstring($paragraph); //adds a string - unlike addRTFpara only has 1 parameter and doesn't insert para codes
}
/* 
CLOSE THE CONNECTION
*/
mysql_close($dbhandle);
/* 
ADD EXECUTION CLAUSE/PAGE AT END
*/

Execution();

/*
REPLACE THE FORMATTING CODES READY FOR RTF 
*/
CodesParser($RTFdata);  //replaces formatting/style codes in text with suitable RTF-based paragraph and outline numbering data

/* 
IF REQUIRED, REPLACE THE PARTY DETAILS READY FOR RTF 
*/
TextSwap($RTFdata, "PartyA","TZMI"); //replaces Party A with the specific client
TextSwap($RTFdata, "PartyC","Recipient");


/* 
WRITE THE DOCUMENT 
*/
writeRTFdoc($RTFdata,"26March2015v1");   //parameters are the RTF data and the proposed filename (excluding the RTF suffix that will be added automatically)
//note for future reference that this function might just write the entire RTF text file to an entry in a DB table, for exporting later, but still retaining it as a complete text record/s in a database.  At present, it writes to a physical file.
echo "done";
?>
