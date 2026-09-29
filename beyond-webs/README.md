# Beyond Webs App v0.0.1

This folder is the document root for `host.beyondimagination.co.technology`.

Beyond Webs v0.0.1 is the VPS session control page, distinct from the BIT OS product page. It helps users configure a metered VPS session, choose the BIT OS flavour that will run inside it, review the selected resources, and start a session request. Usage is described as hourly; this page does not display rates or claim live VPS availability. The page avoids naming a single VPS provider so multiple infrastructure plugs can be added behind the product.

## Host configuration

1. Create an HTTPS subdomain for `host.beyondimagination.co.technology` in the hosting control panel.
2. Map its document root to the deployed `beyond-webs/` folder.
3. The production Beyond host shares sessions automatically. Set this optional PHP environment variable on the primary Beyond ID host when an explicit deployment value is preferred:

   ```text
   BEYOND_WEBS_ORIGIN=https://host.beyondimagination.co.technology
   ```

4. Confirm that `https://host.beyondimagination.co.technology/` loads and that **Continue with Beyond ID** returns to the same subdomain after sign-in.

Beyond ID defaults to this exact HTTPS host and also restricts an explicit `BEYOND_WEBS_ORIGIN` to it. This prevents sign-in returns from going to another external site.
