# RetroPie Cleaner - Handle the mess

Every RetroPie user with a huge amount of Games know the pain of duplicated roms, broken links to the rom, image or video. Manuel fix these things are awful.
 
# Featureset

* Find duplicated entries
* Detect downloaded media files and move it to the rom folder
* Delete corrupted Rom/Image/Video entries (from gamelist.xml)
* Auto map Rom -> Video/Image
* Move unused Media 
* Autobackup old gamelist.xml


# Installation

```
sudo apt-get install php5
git clone https://github.com/Sor3nt/RetroPie-Cleaner.git
cd RetroPie-Cleaner/
sudo php install.php
```

# Usage

```
#: cleaner --help

Retropie - Cleaner - Created by Sor3nt 2017
¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
=> Usage: cleaner [--option[=arguments]]
¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯
Options: 
        --test                  Enable the test mode
        --only-emulator=snes    Comma seperated platform list (Default: autodetect)
        --no-duplicate-check    Ignore duplicated roms
        --no-invalid-entry-fix  Ignore corrupt roms and medias
        --no-auto-map           Do not try to map media to roms
        --no-auto-move          Do not move unused files
        --keep-japan            Prevent moving japan roms (Default: false)
        --keep-country          Keep one of the given names (Default: germany,de,europe,usa,en)

Sample: cleaner --only-emulator=snes,nes,n64
Sample: cleaner --keep-country=europe,usa

```

# What happens when i call "cleaner"?

Called with none options the Cleaner will do the following

### Locate the RetroPie config 'es_systems.cfg'

Custom Version: /home/pi/.emulationstation/es_systems.cfg or

Default Version: /etc/emulationstation/es_systems.cfg

### Loop over every System

Currently i skip "scummvm", "pc", "psx", "port" because these works with multiple files. Support in near future.

### Move any already downloaded files to the rom folder

Copy any files from /opt/retropie/configs/all/emulationstation/downloaded_images/ to the current rom folder.
Split images and videos into his own folder.

### Search for duplicate Roms and unwanted Roms

How the duplicate checker works? Lets say we have these roms:

```
Megaman 1 (USA).zip 
Megaman 1 (Europe).zip 
Megaman 1 (J) [!].zip 
Megaman 1 [DEMO].zip 
Megaman 1 [DEMO] (v1.1).zip 
Megaman 1 [DEMO] (mod).zip 
```

These list will run through a filter that remove all brackets and unwanted content.
Per default the Cleaner has a priority order "germany,de,europe,usa,en" for this detection.

The filter will now return 


```
KEEP:
Megaman 1 (Europe).zip 

DELETE:
Megaman 1 (USA).zip 
Megaman 1 [DEMO].zip 
Megaman 1 [DEMO] (v1.1).zip 
Megaman 1 [DEMO] (mod).zip 
```

Because Europe is before USA in the order list (to change this behavior use the --keep-country command )

The unwanted Roms will be removed from the gamelist.xml and the files are moved to /home/pi/RetroPie/check_roms/

### Validate gamelist.xml entries

This part check the existence of the given options, is the rom, video or image linked but not found anymore, the option (or the complete game) will be removed.

### Map Videos and Images

Try to map the videos or images to the roms.
This works similar to the filter described above. So we can match "Megaman 1 (Spezial Test Video).mp4" to the rom "Megaman 1" without problems.

### Save new gamelist

And finally we save the the new gamelist. On every save, a backup will created.


# Good to know:

* Nothing ist lost!, this tool will never delete any files! All files always moved to /home/pi/RetroPie/check_roms/
* When the Cleaner moved a Video or Image to "check_roms" and a game is placed afterward, just call the tool again and he will move the files back to your rom folder!