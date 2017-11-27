<?php

    if (!file_exists('/opt/cleaner')) {
        echo "Creating folder /opt/cleaner\n";
        mkdir('/opt/cleaner');
        mkdir('/opt/cleaner/src');
    }

    echo "Copy files to /opt/cleaner\n";
    copy('cleaner.php', '/opt/cleaner/cleaner.php');
    copy('src/GameEntry.php', '/opt/cleaner/src/GameEntry.php');
    copy('src/GameList.php', '/opt/cleaner/src/GameList.php');
    copy('src/VideoMatcher.php', '/opt/cleaner/src/VideoMatcher.php');
    copy('src/DuplicateMatcher.php', '/opt/cleaner/src/DuplicateMatcher.php');

    if (!file_exists('/usr/bin/cleaner')){
        echo "Create executabele /usr/bin/cleaner\n";
        $executable = "php /opt/cleaner/cleaner.php";
        file_put_contents('/usr/bin/cleaner', $executable);

        system('chmod +x /usr/bin/cleaner');
    }

    echo "Done. You can use the command cleaner global\n";