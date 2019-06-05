[![Build Status](https://travis-ci.com/SamirBoulil/slub.svg?branch=master)](https://travis-ci.com/SamirBoulil/slub)
[![Maintainability](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/maintainability)](https://codeclimate.com/github/SamirBoulil/slub/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/test_coverage)](https://codeclimate.com/github/SamirBoulil/slub/test_coverage)

# Libraries

[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub?ref=badge_shield)

# Ideas
- Add usernames for actions
- Add emoji in status bar for people who reviewed instead of counts
- Time to review AVG
- Daily reminders of PR to review (per squad :( / channels)

# To refactor
- Slub/Infrastructure/Chat/Slack/SlubBot.php:77 - To rework PRIdentifier should be created with "repository" and "PRNumber" to create the PRIdentifier
- config/services.yaml:19 - set public classes to false: the "test" env should have public true (unless the bug has been fixed)
- Remove filebased ?
- Rework the usage of guzzle mocks + setups in one class common place
- /!\ Remove GetVCSStatus dependency in the putToReviewHandler -> get the information before creating the command
- /!\ There is something fishy going on with the PRIdentifier
- Rework Update CI status command with custom setters

# Test is merged
