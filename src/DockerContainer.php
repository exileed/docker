<?php

namespace Spatie\Docker;

use Spatie\Docker\Exceptions\CouldNotStartDockerContainer;
use Spatie\Macroable\Macroable;
use Symfony\Component\Process\Process;

class DockerContainer
{
    use Macroable;

    public string $image = '';

    public string $name = '';

    public ?string $networkName = '';

    public bool $daemonize = true;

    public bool $privileged = false;

    /** @var \Spatie\Docker\PortMapping[] */
    public array $portMappings = [];

    /** @var \Spatie\Docker\EnvironmentMapping[] */
    public array $environmentMappings = [];

    /** @var array \Spatie\Docker\VolumeMapping[] */
    public array $volumeMappings = [];

    /** @var \Spatie\Docker\LabelMapping[] */
    public array $labelMappings = [];

    /** @var \Spatie\Docker\Option[] */
    public array $options = [];

    public bool $cleanUpAfterExit = true;

    public bool $stopOnDestruct = false;

    public function __construct(string $image, string $name = '')
    {
        $this->image = $image;

        $this->name = $name;
    }

    public static function create(...$args): self
    {
        return new static(...$args);
    }

    public function image(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function daemonize(bool $daemonize = true): self
    {
        $this->daemonize = $daemonize;

        return $this;
    }

    public function privileged(bool $privileged = true): self
    {
        $this->privileged = $privileged;

        return $this;
    }

    public function doNotDaemonize(): self
    {
        $this->daemonize = false;

        return $this;
    }

    public function cleanUpAfterExit(bool $cleanUpAfterExit): self
    {
        $this->cleanUpAfterExit = $cleanUpAfterExit;

        return $this;
    }

    public function setNetwork(string $networkName): self
    {
        $this->networkName = $networkName;

        return $this;
    }

    public function doNotCleanUpAfterExit(): self
    {
        $this->cleanUpAfterExit = false;

        return $this;
    }

    public function mapPort(int $portOnHost, $portOnDocker): self
    {
        $this->portMappings[] = new PortMapping($portOnHost, $portOnDocker);

        return $this;
    }

    public function setEnvironmentVariable(string $envName, string $envValue): self
    {
        $this->environmentMappings[] = new EnvironmentMapping($envName, $envValue);

        return $this;
    }

    /**
     * @param string $optionName
     * @param int|string $valueName
     *
     * @return $this
     */
    public function setOption(string $optionName, $valueName): self
    {
        $this->options[] = new Option($optionName, $valueName);

        return $this;
    }

    public function setVolume(string $pathOnHost, string $pathOnDocker): self
    {
        $this->volumeMappings[] = new VolumeMapping($pathOnHost, $pathOnDocker);

        return $this;
    }

    public function setLabel(string $labelName, string $labelValue): self
    {
        $this->labelMappings[] = new LabelMapping($labelName, $labelValue);

        return $this;
    }

    public function stopOnDestruct(bool $stopOnDestruct = true): self
    {
        $this->stopOnDestruct = $stopOnDestruct;

        return $this;
    }

    public function getStartCommand(): string
    {
        return "docker run {$this->getExtraOptions()} {$this->image}";
    }

    public function start(): DockerContainerInstance
    {
        $command = $this->getStartCommand();

        $process = Process::fromShellCommandline($command);

        $process->run();

        if (! $process->isSuccessful()) {
            throw CouldNotStartDockerContainer::processFailed($this, $process);
        }

        $dockerIdentifier = $process->getOutput();

        return new DockerContainerInstance(
            $this,
            $dockerIdentifier,
            $this->name,
        );
    }

    protected function getExtraOptions(): string
    {
        $extraOptions = [];

        if (count($this->portMappings)) {
            $extraOptions[] = implode(' ', $this->portMappings);
        }

        if (count($this->environmentMappings)) {
            $extraOptions[] = implode(' ', $this->environmentMappings);
        }

        if (count($this->volumeMappings)) {
            $extraOptions[] = implode(' ', $this->volumeMappings);
        }

        if (count($this->labelMappings)) {
            $extraOptions[] = implode(' ', $this->labelMappings);
        }

        if (count($this->options)) {
            $extraOptions[] = implode(' ', $this->options);
        }

        if ($this->name !== '') {
            $extraOptions[] = "--name {$this->name}";
        }

        if ($this->networkName !== '') {
            $extraOptions[] = "--network {$this->networkName}";
        }

        if ($this->daemonize) {
            $extraOptions[] = '-d';
        }

        if ($this->privileged) {
            $extraOptions[] = '--privileged';
        }

        if ($this->cleanUpAfterExit) {
            $extraOptions[] = '--rm';
        }

        return implode(' ', $extraOptions);
    }
}
