Hi! Thanks for the interest in improving the WordPress integration tests with PestPHP

## Reporting an issue

If you found a bug in the code, please open an issue and follow the instructions in the issue template.

## Contributing patches and new features
If you found a bug and want to fix it, or you want to add some new and cool feature, fork the repository, then create a feature branch from the main branch. For instance `feature/some-bug-fix` or `feature/some-cool-new-feature`.

Once you've coded things up, be sure you check that your code is following the coding standards. Also, test that your code isn't breaking anything :)

Then submit a pull request to develop branch. Once I check everything I'll merge the changes into main with correct version correction (noted by the milestone flag and future release tag).

## Running tests

In order for tests to pass, the `wp-content` folder that is copied when installing the composer requirements **must be present**. That is a database dropin that needs to be copied over successfully, otherwise the tests will fail.

You can leave it in, it's in `.gitignore` list.
