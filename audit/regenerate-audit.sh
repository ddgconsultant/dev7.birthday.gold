#!/usr/bin/env bash
set -euo pipefail
A=bf735d5  # DISCOVER HOTFIX
B=de79994  # LOG VIEWER
mkdir -p audit
git log --reverse --date=local --pretty=format:"%h  %cd  %an  %s" $A..$B > audit/commits_DH_to_LV.txt
git diff --name-status $A..$B > audit/files_DH_to_LV.txt
git diff --stat        $A..$B > audit/stats_DH_to_LV.txt
# To rebuild the patch locally (untracked):
# git diff --binary $A $B | gzip -9 > ../AUDIT/hotfix_to_logviewer.patch.gz
