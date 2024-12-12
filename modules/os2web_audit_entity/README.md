# OS2web audit entity

This module tries to log information about entity access and changes.

## Webform submission

This module integrates with [OS2Forms][os2forms-link], which utilizes the Webform module.

If you are logging users who have accessed Webform submissions but no data is being recorded, ensure the patches
provided by this module are applied to the Webform module.

**Note:** The patch cannot be applied via Composer because Composer does not support relative paths to patches outside
the webroot. Additionally, as the location of this module within the site can vary, applying the patch automatically
could potentially break the Composer installation.

### Why this patch

When implementing audit logging for webform submissions in Drupal, particularly to track who accessed the data:

- Using `hook_entity_storage_load()` presents challenges with webform submissions due to their reliance on revisions.
- This is because the hook gets triggered before the storage handler finishes loading the submission data.

To address this issue, a custom hook, `hook_webform_post_load_data()`, is introduced.
This custom hook is invoked after the webform has successfully loaded the submission data for a given submission
revision.

[os2forms-link]: https://github.com/OS2Forms/os2forms
