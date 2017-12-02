<?php

    if (!file_exists('/opt/cleaner')) {
        echo "Creating folder /opt/cleaner\n";
        mkdir('/opt/cleaner');
        mkdir('/opt/cleaner/src');
    }

    echo "Copy files to /opt/cleaner\n";
    copy('cleaner.php', '/opt/cleaner/cleaner.php');

    copy('src/CompareFileNames.php', '/opt/cleaner/src/CompareFileNames.php');
    copy('src/DuplicateMatcher.php', '/opt/cleaner/src/DuplicateMatcher.php');
    copy('src/System.php', '/opt/cleaner/src/System.php');
    copy('src/Emulators.php', '/opt/cleaner/src/Emulators.php');
    copy('src/GameEntry.php', '/opt/cleaner/src/GameEntry.php');
    copy('src/GameList.php', '/opt/cleaner/src/GameList.php');
    copy('src/Helper.php', '/opt/cleaner/src/Helper.php');
    copy('src/Log.php', '/opt/cleaner/src/Log.php');
    copy('src/Matcher.php', '/opt/cleaner/src/Matcher.php');
    copy('src/System.php', '/opt/cleaner/src/System.php');

    if (!file_exists('/usr/bin/cleaner')){
        echo "Create executabele /usr/bin/cleaner\n";
        $executable = "php /opt/cleaner/cleaner.php $@";
        file_put_contents('/usr/bin/cleaner', $executable);

        system('chmod +x /usr/bin/cleaner');
    }

    echo "Done. You can use the command cleaner global\n";