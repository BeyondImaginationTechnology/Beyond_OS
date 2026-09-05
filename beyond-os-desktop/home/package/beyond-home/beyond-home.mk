################################################################################
# Beyond OS Home
################################################################################
BEYOND_HOME_VERSION = 1.0.0-dev.1
BEYOND_HOME_SITE = $(BR2_EXTERNAL_BEYOND_HOME_PATH)/src
BEYOND_HOME_SITE_METHOD = local
BEYOND_HOME_LICENSE = MIT (code), proprietary (artwork)
BEYOND_HOME_LICENSE_FILES = LICENSE CONTENT_RIGHTS.md
BEYOND_HOME_DEPENDENCIES = sdl2 sdl2_ttf host-pkgconf

define BEYOND_HOME_BUILD_CMDS
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$$($(PKG_CONFIG_HOST_BINARY) --cflags sdl2 SDL2_ttf) \
		$(@D)/home.c -o $(@D)/beyond-home $(TARGET_LDFLAGS) \
		$$($(PKG_CONFIG_HOST_BINARY) --libs sdl2 SDL2_ttf) -lm
	$(TARGET_CC) $(TARGET_CFLAGS) -std=c11 -Wall -Wextra -Werror \
		$(@D)/splash.c -o $(@D)/beyond-splash $(TARGET_LDFLAGS)
endef

define BEYOND_HOME_INSTALL_TARGET_CMDS
	$(INSTALL) -D -m 0755 $(@D)/beyond-home $(TARGET_DIR)/usr/bin/beyond-home
	$(INSTALL) -D -m 0755 $(@D)/beyond-splash $(TARGET_DIR)/usr/bin/beyond-splash
	$(INSTALL) -D -m 0644 $(BR2_EXTERNAL_BEYOND_HOME_PATH)/assets/boot.ppm \
		$(TARGET_DIR)/usr/share/beyond-home/boot.ppm
endef

$(eval $(generic-package))
