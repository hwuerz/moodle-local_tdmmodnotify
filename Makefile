#
# TDM: Module modification notification.
#
# @author Luke Carrier <luke@tdm.co>
# @copyright (c) 2014 The Development Manager Ltd
#

.PHONY: all clean

TOP := $(dir $(CURDIR)/$(word $(words $(MAKEFILE_LIST)), $(MAKEFILE_LIST)))

all: build/local_tdmmodnotify.zip

clean:
	rm -rf $(TOP)build

build/local_tdmmodnotify.zip:
	mkdir -p $(TOP)build
	cp -rv $(TOP)src $(TOP)build/tdmmodnotify
	cp $(TOP)README.md $(TOP)build/tdmmodnotify
	cd $(TOP)build \
		&& zip -r local_tdmmodnotify.zip tdmmodnotify
	rm -rfv $(TOP)build/tdmmodnotify
