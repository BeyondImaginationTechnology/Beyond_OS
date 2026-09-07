################################################################################
# BIT OS Core
################################################################################
BEYOND_CORE_VERSION = 1.0.0-dev.1
BEYOND_CORE_SITE = $(BR2_EXTERNAL_BEYOND_CORE_PATH)/src
BEYOND_CORE_SITE_METHOD = local
BEYOND_CORE_LICENSE = MIT (code), proprietary (artwork)
BEYOND_CORE_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_CORE_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_CORE_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/core.c -o $(@D)/beyond-core $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf) -lm
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$(@D)/splash.c -o $(@D)/beyond-splash $(TARGET_LDFLAGS)
endef

define BEYOND_CORE_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-core $(TARGET_DIR)/usr/bin/beyond-core
	$(INSTALL) -D -m 0755 $(@D)/beyond-splash $(TARGET_DIR)/usr/bin/beyond-splash
	$(INSTALL) -D -m 0644 $(BR2_EXTERNAL_BEYOND_CORE_PATH)/assets/boot.ppm \
		$(TARGET_DIR)/usr/share/beyond-core/boot.ppm
	$(INSTALL) -D -m 0644 $(BR2_EXTERNAL_BEYOND_CORE_PATH)/board/x86_64/grub.cfg.in \
		$(TARGET_DIR)/usr/share/beyond-core/grub.cfg.in
endef

$(eval $(generic-package))
