[![Build Status](https://travis-ci.com/SamirBoulil/slub.svg?branch=master)](https://travis-ci.com/SamirBoulil/slub)
[![Maintainability](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/maintainability)](https://codeclimate.com/github/SamirBoulil/slub/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/test_coverage)](https://codeclimate.com/github/SamirBoulil/slub/test_coverage)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub?ref=badge_shield)

# Libraries

Botman: https://github.com/botman/botman


# Todo:
- Add tactical loggers
- Put env variables:
    - Supported repositories
    - Supported channels
    - Messages (GTM / commented/ not gtm) in env variables with defaults

# Ideas
- Add glossary (squad, PR, GTM...)
- get notified *who* GTMed a PR ?
- Be notified *who* merged the PR ?
- be notified when a PR is closed but not merged ?

# To refactor
- Slub/Infrastructure/Chat/Slack/SlubBot.php:77 - To rework PRIdentifier should be created with "repository" and "PRNumber" to create the PRIdentifier
- config/services.yaml:19 - set public classes to false: the "test" env should have public true (unless the bug has been fixed)
- Remove filebased ?


## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub?ref=badge_large)