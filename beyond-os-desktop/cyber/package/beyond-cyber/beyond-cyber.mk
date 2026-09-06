################################################################################
# BIT OS Cyber
################################################################################
BEYOND_CYBER_VERSION = 1.0.0-dev.1
BEYOND_CYBER_SITE = $(BR2_EXTERNAL_BEYOND_CYBER_PATH)/src
BEYOND_CYBER_SITE_METHOD = local
BEYOND_CYBER_LICENSE = MIT (code), proprietary (artwork)
BEYOND_CYBER_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_CYBER_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_CYBER_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/home.c -o $(@D)/beyond-cyber $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf) -lm
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$(@D)/splash.c -o $(@D)/beyond-splash $(TARGET_LDFLAGS)
endef

define BEYOND_CYBER_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-cyber $(TARGET_DIR)/usr/bin/beyond-cyber
	$(INSTALL) -D -m 0755 $(@D)/beyond-splash $(TARGET_DIR)/usr/bin/beyond-splash
	$(INSTALL) -D -m 0644 $(BR2_EXTERNAL_BEYOND_CYBER_PATH)/assets/boot.ppm \
		$(TARGET_DIR)/usr/share/beyond-cyber/boot.ppm
	$(INSTALL) -D -m 0644 $(BR2_EXTERNAL_BEYOND_CYBER_PATH)/board/x86_64/grub.cfg.in \
		$(TARGET_DIR)/usr/share/beyond-cyber/grub.cfg.in
endef

$(eval $(generic-package))
