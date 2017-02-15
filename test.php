#!/usr/bin/env php
<?php

$branch="refs/heads/master";

$branch_without_refs=str_replace("refs/heads/","",$branch);
$git_ssh_url="git@gitlab.my.domain:test/test.git";

$tmp_int_project=str_replace("git@gitlab.my.domain:"," ",$git_ssh_url);
$tmp_int_project=str_replace("/"," ",$tmp_int_project);

sscanf($tmp_int_project,"%s",$int_project);
echo $int_project;
