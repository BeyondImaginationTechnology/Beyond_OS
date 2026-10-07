################################################################################
# BIT OS Academy Learning Hub
################################################################################
BEYOND_ACADEMY_VERSION = 0.1.0-dev.1
BEYOND_ACADEMY_SITE = $(BR2_EXTERNAL_BEYOND_CORE_PATH)/../academy/src
BEYOND_ACADEMY_SITE_METHOD = local
BEYOND_ACADEMY_LICENSE = MIT (code), proprietary (product identity)
BEYOND_ACADEMY_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_ACADEMY_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_ACADEMY_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/academy.c -o $(@D)/beyond-academy $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf)
endef

define BEYOND_ACADEMY_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-academy $(TARGET_DIR)/usr/bin/beyond-academy
endef

$(eval $(generic-package))
