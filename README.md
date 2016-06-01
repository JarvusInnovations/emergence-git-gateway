# emergence-git-gateway

## Roadmap

### Short-term

- [ ] prevent updates to parent layer from overriding local layer content
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