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
- Slub/Infrastructure/Chat/Slack/SlubBot.php:77 - To rework PRIdentifier should be created with "repository" and "PRNumber" to create the PRIdentifier
- Slub\Infrastructure\Chat\Slack\GetChannelInformation::setSlubBot find a way to get create another client from scratch or get the bot in the query function without doing a cercular reference
- config/services.yaml:19 - set public classes to false: the "test" env should have public true (unless the bug has been fixed)

- Define different configs for repositories in tests and in prod
- Define different configs for squad channel in tests and in prod
