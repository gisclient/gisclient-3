<?php

namespace GisClient\Author\Controller;

use GisClient\Author\Utils\MapImage;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller to create and download geo-referenced image
 *
 * @author Daniel Degasperi <daniel.degasperi@r3-gis.com>
 */
class DownloadController
{
    /**
     * Create error response
     *
     * @param string $errorMessage
     * @param integer $httpStatus
     * @return JsonResponse
     */
    private function createErrorResponse($errorMessage, $httpStatus = JsonResponse::HTTP_BAD_REQUEST)
    {
        print_debug($errorMessage, null, 'download');
        $data = [
            'result' => 'error',
            'error' => $errorMessage
        ];
        return new JsonResponse($data, $httpStatus);
    }

    /**
     * Create a downloadable image
     */
    public function createImageAction(Request $request)
    {
        $format = $request->request->get("format", null);
        $tiles = $request->request->get("tiles", null);
        $viewSize = $request->request->get("viewport_size", null);
        $srid = $request->request->get("srid", null);
        $dpi = $request->request->get("dpi", null);
        $extent = $request->request->get("extent", null);
        $scaleMode = $request->request->get("scale_mode", null);
        $scale = $request->request->get("scale", null);
        $scalebar = $request->request->get("scalebar", null);
        $center = $request->request->get("center", null);
        $fixedSize = $request->request->get("fixed_size", null);

        // TODO: handle request parameteers in a more systematic way, suing a single
        // procedure checking required/optional, type and value range

        $options = array(
            'image_format'=>'gtiff',
            'output_format'=>'geotiff'
        );

        if ($format == 'png') {
            $options['image_format'] = 'png';
            $options['output_format'] = 'png';
        } elseif ($format == 'jpeg') {
            $options['image_format'] = 'jpeg';
            $options['output_format'] = 'jpeg';
        }

        if (empty($tiles) || !is_array($tiles)) {
            return $this->createErrorResponse('No tiles');
        }

        if (empty($viewSize) || !is_array($viewSize) || count($viewSize) != 2) {
            return $this->createErrorResponse('No size');
        }

        if (empty($srid)) {
            return $this->createErrorResponse('No srid');
        }
        if (strpos($srid, ':') !== false) {
            list($options['auth_name'], $srid) = explode(':', $srid);
        }

        $options['dpi'] = MAP_DPI;
        if (!empty($dpi) && is_numeric($dpi)) {
            $options['dpi'] = (int) $dpi;
        }
        $pixPerMetreFromDPI = $options['dpi'] * 100 / 2.54;

        if (!empty($extent)) {
            $options['extent'] = explode(',', $extent);
        } else {
            // we could eventually try to reconstruct the extent from the viewport size,
            // but is it worth to complicate the code?
            return $this->createErrorResponse('missing mandatory parameter "extent"');
        }
        if (!empty($scaleMode)) {
            $options['scale_mode'] = $scaleMode;
        }
        if (!empty($fixedSize)) {
            $options['fixed_size'] = $fixedSize;
        }

        if (!empty($scale)) {
            $options['scale'] = $scale;
        } else {
            $scaleWidth = (int)$pixPerMetreFromDPI * ($options['extent'][2] - $options['extent'][0]) / $viewSize[0];
            $scaleHeight = (int)$pixPerMetreFromDPI * ($options['extent'][3] - $options['extent'][1]) / $viewSize[1];

            $options['scale'] = ($scaleWidth < $scaleHeight)? $scaleWidth : $scaleHeight;
        }

        if (!empty($center)) {
            $options['center'] = $center;
        }

        $imageSize = array(
            0 => (int)$pixPerMetreFromDPI * ($options['extent'][2] - $options['extent'][0]) / $options['scale'],
            1 => (int)$pixPerMetreFromDPI * ($options['extent'][3] - $options['extent'][1]) / $options['scale'],
        );

        if (isset($scalebar)) {
            $options['scalebar'] = $scalebar;
        }

        try {
            $mapImage = new MapImage(trim(PUBLIC_URL, '/'), $tiles, $imageSize, $srid, $options);
            $imageUrl = $mapImage->getImageUrl();
        } catch (\Exception $e) {
            print_debug($e->getTraceAsString(), null, 'download');
            return $this->createErrorResponse($e->getMessage(), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $data = [
            'result' => 'ok',
            'file' => $imageUrl,
            'format' => $options['output_format'],
        ];
        return new JsonResponse($data);
    }

    /**
     * Download image
     */
    public function downloadImageAction(Request $request)
    {
        $filename = basename($request->query->get("filename", null));
        if (empty($filename)) {
            return $this->createErrorResponse('Missing parameter "filename"');
        }

        try {
            $imagePath = ROOT_PATH.'tmp/files';
            $response = new BinaryFileResponse($imagePath.'/'.$filename);
            $response->setContentDisposition('attachment', $filename);
            $response->deleteFileAfterSend(true);
            return $response;
        } catch (FileNotFoundException $e) {
            return $this->createErrorResponse('File not found "'.$filename.'"', BinaryFileResponse::HTTP_NOT_FOUND);
        }
    }
}
