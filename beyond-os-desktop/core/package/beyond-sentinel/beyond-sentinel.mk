################################################################################
# BIT OS Sentinel Console
################################################################################
BEYOND_SENTINEL_VERSION = 0.1.0-dev.1
BEYOND_SENTINEL_SITE = $(BR2_EXTERNAL_BEYOND_CORE_PATH)/../sentinel/src
BEYOND_SENTINEL_SITE_METHOD = local
BEYOND_SENTINEL_LICENSE = MIT (code), proprietary (product identity)
BEYOND_SENTINEL_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_SENTINEL_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_SENTINEL_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/sentinel.c -o $(@D)/beyond-sentinel $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf)
endef

define BEYOND_SENTINEL_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-sentinel $(TARGET_DIR)/usr/bin/beyond-sentinel
endef

$(eval $(generic-package))
