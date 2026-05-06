<?php
/**
 * System method handlers for MCP requests.
 *
 * @package McpAdapter
 */

declare( strict_types=1 );

namespace Bluehost\Plugin\WP\MCP\Handlers\System;

use Bluehost\Plugin\WP\McpSchema\Common\AbstractDataTransferObject;
use Bluehost\Plugin\WP\McpSchema\Common\Protocol\DTO\Result;

/**
 * Handles system-related MCP methods.
 */
class SystemHandler {
	/**
	 * Handles the ping request.
	 *
	 * @return \Bluehost\Plugin\WP\McpSchema\Common\AbstractDataTransferObject Empty result DTO per MCP specification.
	 */
	public function ping(): AbstractDataTransferObject {
		return Result::fromArray( array() );
	}
}
