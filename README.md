# emergence-git-gateway

**DEPRECATED:** This code has been merged into [`skeleton-v1`](https://github.com/JarvusInnovations/emergence-skeleton) and [`layer-vfs`](https://github.com/EmergencePlatform/layer-vfs)

## Roadmap

### Short-term

- [X] prevent updates to parent layer from overriding local layer content
- [ ] block pushing (temporarily)
- [ ] generate smarter commit messages
  - prefix `master` commits with name of layer?
- [ ] set site-wide lock during synchronization
- [ ] record hostname alongside index in commits
- [ ] append synchronization stats to log file

### Long-term

- [ ] track modification and cache hashes within trees
- [ ] allow pushing to `local` and `master` branches
- [ ] fire events for HTTP activity and git hooks
- [ ] record overridden changes in branch and nullify on merge into master
- [ ] write each change to cached trees directly instead of aggregating in commit first