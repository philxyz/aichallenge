<?php

// include guard
if (!isset($JPC_CONTEST_SUBMISSION_PHP__)) {
$JPC_CONTEST_SUBMISSION_PHP__ = 1;

/*
 * Submission Statuses
 * 10: entry record created in database.
 * 15: entry archive file placed in temporary directory. Still has to be
 *     transferred to where the compile script expects to find it.
 * 20: Ready to be unzipped and compiled.
 * 24: entry currently in the process of being compiled. The compiler script
 *     should not try to compile this entry now.
 * 27: entry has compiled successfully, and is awaiting test cases.
 * 30: error receiving submission zip file.
 * 40: compiled successfully and passed test cases.  Ready to be run.
 * 50: error while unzipping submission file.
 * 60: problem with submission file.
 * 70: error while compiling submission.
 * 80: compiled successfully but failed test cases.
 */

function create_new_submission_for_current_user() {
  $username = current_username();
  if ($username = NULL) {
    return FALSE;
  }
  $query = "INSERT INTO submissions (" .
    "user_id,status,timestamp) VALUES (" .
    current_user_id() . "," .
    "10,CURRENT_TIMESTAMP)";
  $successfull = mysql_query($query);
  if($successfull){
    mysql_query("UPDATE submissions SET latest = 0 WHERE user_id ='".current_user_id()."'");
  }
  return $successfull;
}

function current_submission_id() {
  $user_id = current_user_id();
  if ($user_id == NULL) {
    return -1;
  }
  $query = "SELECT * FROM submissions " .
    "WHERE user_id = " . $user_id . " ORDER BY timestamp DESC LIMIT 1";
  $result = mysql_query($query);
  if (!$result) {
    print $query . "\n";
    print mysql_error() . "\n";
    return -1;
  }
  if ($row = mysql_fetch_assoc($result)) {
    return $row['submission_id'];
  } else {
    return -1;
  }
}

function current_submission_status() {
  $user_id = current_user_id();
  if ($user_id = NULL) {
    return -1;
  }
  $query = "SELECT TOP 1 * FROM submissions " .
    "WHERE user_id = " . $user_id . " ORDER BY timestamp DESC";
  $result = mysql_query($query);
  if ($row = mysql_fetch_assoc($result)) {
    return $row['status'];
  } else {
    return -1;
  }
}

/* 
 * Returns true if it finds a submission from within the last 10 minutes that
 * either succesfully entered the contest or is still in the process of
 * entering.
 */
function has_recent_submission() {
  $user_id = current_user_id();
  if ($user_id == NULL) {
    return FALSE;
  }
  $query = "SELECT COUNT(*) FROM submissions WHERE user_id='".$user_id."' AND ".
    "(status < 30 OR status=40) AND timestamp >= (NOW() - INTERVAL 10 MINUTE)";
  $result = mysql_query($query);
  if (!$row = mysql_fetch_row($result)) {
    return FALSE;
  }
  if ($row[0] == 0) {
    return FALSE;
  }
  return TRUE;
}

function submission_status($submission_id) {
  $query = "SELECT * FROM submissions " . "WHERE submission_id = " . $submission_id;
  $result = mysql_query($query);
  if ($row = mysql_fetch_assoc($result)) {
    return $row['status'];
  } else {
    return -1;
  }
}

function update_current_submission_status($new_status) {
  $submission_id = current_submission_id();
  if ($submission_id < 0) {
    print "<p>submission_id = " . $submission_id . "</p>";
    return FALSE;
  }
  $user_id = current_user_id();
  if ($user_id < 0) {
    print "<p>user_id = " . $user_id . "</p>";
    return FALSE;
  }
  $query = "UPDATE submissions SET status = " . $new_status .
    " WHERE submission_id = " . $submission_id . " AND user_id = " . $user_id;
  //print "<p>query = " . $query . "</p>";
  return mysql_query($query);
}

function setup_submission_directory($submission_directory) {
  if (!create_new_submission_for_current_user()) {
    print "Failed to setup new submission in database.\n";
    return FALSE;
  }
  $submission_id = current_submission_id();
  if ($submission_id < 0) {
    print "Failed to get submission id.\n";
    return FALSE;
  }
  $directory_name = $submission_directory . $submission_id;
  //mkdir($directory_name);
  return TRUE;
}

} // include guard
?>
