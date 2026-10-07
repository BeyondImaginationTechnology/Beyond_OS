################################################################################
# BIT OS Gaming Hub
################################################################################
BEYOND_GAMING_VERSION = 0.1.0-dev.1
BEYOND_GAMING_SITE = $(BR2_EXTERNAL_BEYOND_CORE_PATH)/../gaming/src
BEYOND_GAMING_SITE_METHOD = local
BEYOND_GAMING_LICENSE = MIT (code), proprietary (product identity)
BEYOND_GAMING_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_GAMING_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_GAMING_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/gaming.c -o $(@D)/beyond-gaming $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf)
endef

define BEYOND_GAMING_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-gaming $(TARGET_DIR)/usr/bin/beyond-gaming
endef

$(eval $(generic-package))
