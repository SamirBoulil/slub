[![Build Status](https://travis-ci.com/SamirBoulil/slub.svg?branch=master)](https://travis-ci.com/SamirBoulil/slub)
[![Maintainability](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/maintainability)](https://codeclimate.com/github/SamirBoulil/slub/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/test_coverage)](https://codeclimate.com/github/SamirBoulil/slub/test_coverage)

# Libraries

Botman: https://github.com/botman/botman


# Todo:
- Remove interfaces from test coverage in phpunit
- Add test coverage 100% ?

# Later
- get notified who GTMed a PR

# To refactor
- Remove isRed() and isGreen() they are only used for tests => use normalize()['CI_STATUS'] instead
- Rename CIIsRed, CIIsGreen for Green, Red
- Refactor EventsSpy
    - Add unit tests
    - Use array instead of 1 property / event
