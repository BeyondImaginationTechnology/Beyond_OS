################################################################################
# Beyond Imagination OS flavour profile
################################################################################
BEYOND_PROFILE_VERSION = 1.0.0-dev.1
BEYOND_PROFILE_SITE = $(BR2_EXTERNAL_BEYOND_CORE_PATH)/../flavours
BEYOND_PROFILE_SITE_METHOD = local
BEYOND_PROFILE_LICENSE = proprietary (product profile metadata)

define BEYOND_PROFILE_INSTALL_TARGET_CMDS
	$(INSTALL) -d -m 0755 $(TARGET_DIR)/usr/share/beyond-imagination-os
	$(INSTALL) -D -m 0644 $(@D)/catalog.json \
		$(TARGET_DIR)/usr/share/beyond-imagination-os/catalog.json
	$(INSTALL) -D -m 0644 $(@D)/profiles.json \
		$(TARGET_DIR)/usr/share/beyond-imagination-os/profiles.json
	printf '%s\n' '$(BR2_BEYOND_PROFILE_ID)' > \
		$(TARGET_DIR)/usr/share/beyond-imagination-os/flavour
endef

$(eval $(generic-package))
