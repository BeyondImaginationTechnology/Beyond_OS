################################################################################
# BIT OS Creator Hub
################################################################################
BEYOND_CREATOR_VERSION = 0.1.0-dev.1
BEYOND_CREATOR_SITE = $(BR2_EXTERNAL_BEYOND_CORE_PATH)/../creator/src
BEYOND_CREATOR_SITE_METHOD = local
BEYOND_CREATOR_LICENSE = MIT (code), proprietary (product identity)
BEYOND_CREATOR_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_CREATOR_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_CREATOR_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/creator.c -o $(@D)/beyond-creator $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf) -lm
endef

define BEYOND_CREATOR_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-creator $(TARGET_DIR)/usr/bin/beyond-creator
endef

$(eval $(generic-package))
