<div class="modal fade" id="postTransferRouteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="postTransferRouteTitle">
                    Carte itinéraire
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-lg-8 mb-3 mb-lg-0">
                        <div id="postTransferRouteMap" class="post-transfer-route-map"></div>
                        <div id="postTransferRouteStatus" class="small text-muted mt-2">Sélectionnez un trajet.</div>
                    </div>
                    <div class="col-lg-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Étapes</strong>
                            <a id="postTransferRouteGoogleLink" href="#" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                                Google Maps
                            </a>
                        </div>
                        <ol id="postTransferRouteSteps" class="post-transfer-route-steps"></ol>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
