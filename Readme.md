[![Build Status](https://travis-ci.com/SamirBoulil/slub.svg?branch=master)](https://travis-ci.com/SamirBoulil/slub)
[![Maintainability](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/maintainability)](https://codeclimate.com/github/SamirBoulil/slub/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/test_coverage)](https://codeclimate.com/github/SamirBoulil/slub/test_coverage)

# Libraries

Botman: https://github.com/botman/botman


# Todo:
- Add test coverage 100% ?
- Add glossary (squad, PR, GTM...)

# Ideas
- get notified *who* GTMed a PR ?
- Be notified *who* merged the PR ?
- be notified when a PR is closed but not merged ?

# To refactor
- Todo: Rework Slub\Infrastructure\Chat\Slack\GetChannelInformation to be testable with guzzle
- Slub/Infrastructure/Chat/Slack/SlubBot.php:77 - To rework PRIdentifier should be created with "repository" and "PRNumber" to create the PRIdentifier
- config/services.yaml:19 - set public classes to false: the "test" env should have public true (unless the bug has been fixed)
- Send a message when 2 GTMs only and change the behats accordingly
