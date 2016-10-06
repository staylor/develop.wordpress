<?php
namespace WP;

trait Mustache {
	protected $templatePath;
	protected $partialsPath;
	protected $engine;
	protected $config;

	public function setTemplatePath( $path ) {
		$this->templatePath = $path;
	}

	public function setPartialsPath( $path ) {
		$this->partialsPath = $path;
	}

	/**
	 *
	 * @return \Mustache_Loader_FilesystemLoader
	 */
	public function getLoader(): \Mustache_Loader_FilesystemLoader
	{
		if ( ! $this->templatePath ) {
			$this->templatePath = ABSPATH . 'templates';
		}
		return new \Mustache_Loader_FilesystemLoader( $this->templatePath );
	}

	/**
	 *
	 * @return \Mustache_Loader_FilesystemLoader
	 */
	public function getPartialsLoader(): \Mustache_Loader_FilesystemLoader
	{
		if ( ! $this->partialsPath ) {
			$this->partialsPath = ABSPATH . 'templates';
		}
		return new \Mustache_Loader_FilesystemLoader( $this->partialsPath );
	}

	public function setConfig( array $config = [] ) {
		$this->config = array_merge( [
			'pragmas' => [
				\Mustache_Engine::PRAGMA_BLOCKS
			],
			'loader' => $this->getLoader(),
			'partials_loader' => $this->getPartialsLoader()
		], $config );
	}

	public function getConfig(): array
	{
		if ( ! $this->config ) {
			$this->setConfig();
		}

		return $this->config;
	}

	/**
	 *
	 * @return \Mustache_Engine
	 */
	public function getEngine(): \Mustache_Engine
	{
		if ( ! isset( $this->engine ) ) {
			$config = $this->getConfig();

			$this->engine = new \Mustache_Engine( $config );
		}
		return $this->engine;
	}

	/**
	 *
	 * @param string $template
	 * @param array $data
	 * @return string
	 */
	public function render( string $template, $data ): string
	{
		return $this->getEngine()->render( $template, $data );
	}
}

