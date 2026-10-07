# BIT OS Academy 0.1

Academy is an offline-ready learning workspace for students and educators. Its
native Learning Hub provides course and assignment folders, a quiet focus tool,
and clear student/teacher boundaries without claiming a managed classroom
service.

The shared Core builder selects this profile with:

```sh
BEYOND_FLAVOUR=academy BEYOND_BUILD_DIR=../academy/out bash ../core/build.sh build
```

The image identifies itself as `BIT OS Academy v0.1 (Development Preview)` and
starts `/usr/bin/beyond-academy` for the Academy profile.
